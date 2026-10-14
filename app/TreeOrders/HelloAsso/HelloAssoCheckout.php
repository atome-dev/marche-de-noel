<?php

namespace App\TreeOrders\HelloAsso;

use App\Exceptions\HelloAssoUnavailable;
use App\Exceptions\TreeOrdersClosed;
use App\Models\TreeOrder;
use App\TreeOrders\TreeCatalog;
use App\TreeOrders\TreeOrdering;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Online payment of a tree order: send the family to HelloAsso, then
 * confirm the payment by reading it back from HelloAsso. What the browser
 * or a notification says is never trusted.
 */
class HelloAssoCheckout
{
    public function __construct(
        private HelloAssoClient $client,
        private TreeCatalog $catalog,
        private TreeOrdering $ordering,
    ) {}

    /**
     * URL of the HelloAsso payment page.
     *
     * @throws TreeOrdersClosed
     * @throws HelloAssoUnavailable
     */
    public function start(TreeOrder $order): string
    {
        if ($order->isPaid() || $order->isCancelled() || ! $this->client->isConfigured()) {
            throw new InvalidArgumentException(__('trees.errors.not_payable'));
        }

        if ($this->catalog->isClosed()) {
            throw new TreeOrdersClosed;
        }

        $checkout = $this->client->createCheckout(
            $order,
            returnUrl: route('trees.payment.return', $order),
            backUrl: route('trees.payment.back', $order),
            errorUrl: route('trees.payment.error', $order),
        );

        $order->forceFill(['helloasso_checkout_id' => $checkout['id']])->save();

        return $checkout['redirectUrl'];
    }

    /**
     * Whether the order is paid, after checking its last checkout intent:
     * same order in the metadata, same total, an authorized payment.
     *
     * @throws HelloAssoUnavailable
     */
    public function confirm(TreeOrder $order): bool
    {
        if ($order->isPaid()) {
            return true;
        }

        if ($order->helloasso_checkout_id === null) {
            return false;
        }

        $checkout = $this->client->fetchCheckout($order->helloasso_checkout_id);
        $paidOrder = $checkout['order'] ?? null;

        if (! is_array($paidOrder)) {
            return false;
        }

        $authorized = collect($paidOrder['payments'] ?? [])->contains(fn (mixed $payment) => ($payment['state'] ?? null) === 'Authorized');
        $sameOrder = (int) ($checkout['metadata']['tree_order_id'] ?? 0) === $order->id;
        $sameTotal = (int) ($paidOrder['amount']['total'] ?? -1) === $order->total_cents;

        if (! ($authorized && $sameOrder && $sameTotal)) {
            Log::warning('HelloAsso checkout does not match the tree order.', [
                'tree_order_id' => $order->id,
                'checkout_id' => $order->helloasso_checkout_id,
                'authorized' => $authorized,
                'same_order' => $sameOrder,
                'same_total' => $sameTotal,
            ]);

            return false;
        }

        $this->ordering->markPaidByHelloAsso($order, (int) $paidOrder['id']);

        return true;
    }
}
