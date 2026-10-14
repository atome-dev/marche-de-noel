<?php

namespace App\TreeOrders;

use App\Enums\TreePaymentMethod;
use App\Exceptions\TreeOrdersClosed;
use App\Mail\TreeOrderPaid;
use App\Mail\TreeOrderPlaced;
use App\Models\TreeOrder;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Throwable;

/**
 * Tree orders of the families and of people without an account, their
 * payment (HelloAsso or cheque) and their pickup.
 */
class TreeOrdering
{
    public function __construct(private TreeCatalog $catalog) {}

    /**
     * @param  array<string, int>  $quantities  size key => number of trees
     *
     * @throws TreeOrdersClosed
     */
    public function placeForUser(User $user, array $quantities, string $phone): TreeOrder
    {
        $this->ensureOpen();

        return $this->place($quantities, [
            'user_id' => $user->id,
            'customer_name' => $user->first_name.' '.$user->last_name,
            'customer_phone' => trim($phone),
            'customer_email' => $user->email,
            'created_by' => $user->id,
        ]);
    }

    /**
     * Paper order handed to a manager, accepted after the deadline too.
     *
     * @param  array<string, int>  $quantities  size key => number of trees
     */
    public function placeForGuest(User $manager, array $quantities, string $name, string $phone, ?string $email, bool $chequeReceived): TreeOrder
    {
        return $this->place($quantities, [
            'user_id' => null,
            'customer_name' => trim($name),
            'customer_phone' => trim($phone),
            'customer_email' => filled($email) ? trim($email) : null,
            'created_by' => $manager->id,
        ], $chequeReceived ? $manager : null);
    }

    /**
     * Families cancel a pending order before the deadline; managers cancel
     * any order (a HelloAsso refund is made on HelloAsso).
     *
     * @throws TreeOrdersClosed
     */
    public function cancel(TreeOrder $order, bool $byManager = false): void
    {
        if ($order->isCancelled()) {
            return;
        }

        if (! $byManager) {
            if ($order->isPaid()) {
                throw new InvalidArgumentException(__('trees.errors.not_cancellable'));
            }

            $this->ensureOpen();
        }

        $order->forceFill(['cancelled_at' => now()])->save();
    }

    public function markChequeReceived(TreeOrder $order, User $manager): void
    {
        if ($order->isCancelled() || $order->isPaid()) {
            throw new InvalidArgumentException("Order [{$order->id}] cannot be marked as paid.");
        }

        $order->forceFill([
            'payment_method' => TreePaymentMethod::Cheque,
            'paid_at' => now(),
            'paid_by' => $manager->id,
        ])->save();

        $this->mail($order, new TreeOrderPaid($order));
    }

    public function unmarkCheque(TreeOrder $order): void
    {
        if ($order->payment_method !== TreePaymentMethod::Cheque || $order->isPickedUp()) {
            throw new InvalidArgumentException("Payment of order [{$order->id}] cannot be undone.");
        }

        $order->forceFill(['payment_method' => null, 'paid_at' => null, 'paid_by' => null])->save();
    }

    /**
     * Idempotent: HelloAsso may notify several times, and the browser return
     * confirms the same payment. Returns false when it was already recorded.
     */
    public function markPaidByHelloAsso(TreeOrder $order, int $helloAssoOrderId): bool
    {
        $recorded = DB::transaction(function () use ($order, $helloAssoOrderId): bool {
            $locked = TreeOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->isPaid()) {
                return false;
            }

            $locked->forceFill([
                'payment_method' => TreePaymentMethod::HelloAsso,
                'paid_at' => now(),
                'helloasso_order_id' => $helloAssoOrderId,
            ])->save();

            return true;
        });

        if ($recorded) {
            $order->refresh();
            $this->mail($order, new TreeOrderPaid($order));
        }

        return $recorded;
    }

    public function setPickedUp(TreeOrder $order, bool $pickedUp): void
    {
        if (! $order->isPaid() || $order->isCancelled()) {
            throw new InvalidArgumentException("Order [{$order->id}] is not paid.");
        }

        $order->forceFill(['picked_up_at' => $pickedUp ? now() : null])->save();
    }

    /**
     * @param  array<string, int>  $quantities
     * @param  array<string, mixed>  $attributes
     */
    private function place(array $quantities, array $attributes, ?User $chequeReceivedBy = null): TreeOrder
    {
        $lines = $this->lines($quantities);

        $order = DB::transaction(function () use ($lines, $attributes, $chequeReceivedBy): TreeOrder {
            $order = new TreeOrder($attributes);
            $order->total_cents = array_sum(array_map(fn (array $line) => $line['unit_price_cents'] * $line['quantity'], $lines));

            if ($chequeReceivedBy !== null) {
                $order->forceFill([
                    'payment_method' => TreePaymentMethod::Cheque,
                    'paid_at' => now(),
                    'paid_by' => $chequeReceivedBy->id,
                ]);
            }

            $order->save();
            $order->lines()->createMany($lines);

            return $order->load('lines');
        });

        $this->mail($order, $order->isPaid() ? new TreeOrderPaid($order) : new TreeOrderPlaced($order));

        return $order;
    }

    /**
     * @param  array<string, int>  $quantities
     * @return list<array{size_key: string, label: string, height: string, unit_price_cents: int, quantity: int}>
     */
    private function lines(array $quantities): array
    {
        $lines = [];

        foreach ($quantities as $key => $quantity) {
            $quantity = (int) $quantity;

            if ($quantity === 0) {
                continue;
            }

            $size = $this->catalog->find((string) $key);

            if ($size === null || $quantity < 0 || $quantity > $this->catalog->maxQuantity()) {
                throw new InvalidArgumentException("Invalid quantity [{$quantity}] for size [{$key}].");
            }

            $lines[] = [
                'size_key' => $size->key,
                'label' => $size->name,
                'height' => $size->height,
                'unit_price_cents' => $size->priceCents,
                'quantity' => $quantity,
            ];
        }

        if ($lines === []) {
            throw new InvalidArgumentException(__('trees.form.no_tree'));
        }

        return $lines;
    }

    private function ensureOpen(): void
    {
        if ($this->catalog->isClosed()) {
            throw new TreeOrdersClosed;
        }
    }

    /**
     * A failed mail never undoes an order or a payment.
     */
    private function mail(TreeOrder $order, Mailable $mail): void
    {
        if (blank($order->customer_email)) {
            return;
        }

        try {
            Mail::to($order->customer_email)->send($mail);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
