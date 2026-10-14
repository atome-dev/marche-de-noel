<?php

namespace App\Livewire\TreeOrders;

use App\Exceptions\HelloAssoUnavailable;
use App\Exceptions\TreeOrdersClosed;
use App\Models\TreeOrder;
use App\TreeOrders\HelloAsso\HelloAssoCheckout;
use App\TreeOrders\HelloAsso\HelloAssoClient;
use App\TreeOrders\TreeCatalog;
use App\TreeOrders\TreeOrdering;
use App\TreeOrders\TreeSize;
use Closure;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Christmas tree order page of the families, with their orders.
 *
 * @property-read array<string, TreeSize> $sizes
 * @property-read int $totalCents
 * @property-read Collection<int, TreeOrder> $orders
 * @property-read bool $isClosed
 * @property-read bool $onlinePayment
 */
class OrderForm extends Component
{
    /** @var array<string, int> size key => number of trees */
    public array $quantities = [];

    public string $phone = '';

    public function mount(): void
    {
        $this->resetQuantities();
        $this->phone = (string) Auth::user()->phone;
    }

    public function increment(string $key): void
    {
        if (isset($this->sizes[$key])) {
            $this->quantities[$key] = min(app(TreeCatalog::class)->maxQuantity(), (int) ($this->quantities[$key] ?? 0) + 1);
        }
    }

    public function decrement(string $key): void
    {
        if (isset($this->sizes[$key])) {
            $this->quantities[$key] = max(0, (int) ($this->quantities[$key] ?? 0) - 1);
        }
    }

    public function orderByCheque(): void
    {
        $order = $this->place();

        if ($order !== null) {
            Flux::toast(variant: 'success', text: __('trees.toasts.placed', ['number' => $order->number]));
        }
    }

    public function orderAndPay(HelloAssoCheckout $checkout): void
    {
        if (! $this->onlinePayment) {
            return;
        }

        $order = $this->place();

        if ($order !== null) {
            $this->startPayment($order, $checkout);
        }
    }

    public function pay(int $orderId, HelloAssoCheckout $checkout): void
    {
        $order = TreeOrder::query()->findOrFail($orderId);
        $this->authorize('update', $order);

        $this->startPayment($order, $checkout);
    }

    public function cancel(int $orderId, TreeOrdering $ordering): void
    {
        $order = TreeOrder::query()->findOrFail($orderId);
        $this->authorize('update', $order);

        try {
            $ordering->cancel($order);
            Flux::toast(variant: 'success', text: __('trees.toasts.cancelled', ['number' => $order->number]));
        } catch (TreeOrdersClosed|InvalidArgumentException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }

        unset($this->orders);
    }

    /**
     * @return array<string, TreeSize>
     */
    #[Computed]
    public function sizes(): array
    {
        return app(TreeCatalog::class)->sizes();
    }

    #[Computed]
    public function totalCents(): int
    {
        return collect($this->sizes)->sum(fn (TreeSize $size) => $size->priceCents * (int) ($this->quantities[$size->key] ?? 0));
    }

    /**
     * @return Collection<int, TreeOrder>
     */
    #[Computed]
    public function orders(): Collection
    {
        return Auth::user()->treeOrders()->with('lines')->latest('id')->get();
    }

    #[Computed]
    public function isClosed(): bool
    {
        return app(TreeCatalog::class)->isClosed();
    }

    #[Computed]
    public function onlinePayment(): bool
    {
        return app(HelloAssoClient::class)->isConfigured();
    }

    public function render(): View
    {
        $catalog = app(TreeCatalog::class);

        return view('livewire.tree-orders.order-form', [
            'deadline' => $catalog->deadline(),
            'pickupDate' => $catalog->pickupDate(),
            'maxQuantity' => $catalog->maxQuantity(),
        ])->title(__('trees.title'));
    }

    private function place(): ?TreeOrder
    {
        if ($this->isClosed) {
            Flux::toast(variant: 'danger', text: __('trees.errors.closed'));

            return null;
        }

        $this->validate([
            'quantities' => ['array', function (string $attribute, mixed $value, Closure $fail): void {
                if (array_sum(array_map('intval', (array) $value)) === 0) {
                    $fail(__('trees.form.no_tree'));
                }
            }],
            'quantities.*' => ['integer', 'min:0', 'max:'.app(TreeCatalog::class)->maxQuantity()],
            'phone' => ['required', 'string', 'max:30'],
        ], attributes: ['phone' => __('trees.form.phone')]);

        try {
            $order = app(TreeOrdering::class)->placeForUser(Auth::user(), $this->quantities, $this->phone);
        } catch (TreeOrdersClosed|InvalidArgumentException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return null;
        }

        $this->resetQuantities();
        unset($this->orders);

        return $order;
    }

    private function startPayment(TreeOrder $order, HelloAssoCheckout $checkout): void
    {
        try {
            $this->redirect($checkout->start($order));
        } catch (HelloAssoUnavailable $exception) {
            report($exception);
            Flux::toast(variant: 'warning', text: __('trees.toasts.helloasso_unavailable'));
        } catch (TreeOrdersClosed|InvalidArgumentException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    private function resetQuantities(): void
    {
        $this->quantities = array_map(fn () => 0, $this->sizes);
    }
}
