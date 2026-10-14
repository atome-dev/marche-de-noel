<?php

namespace App\Http\Controllers\TreeOrders;

use App\Exceptions\HelloAssoUnavailable;
use App\Http\Controllers\Controller;
use App\Models\TreeOrder;
use App\TreeOrders\HelloAsso\HelloAssoCheckout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Server notification of HelloAsso (declared in its back office). Its
 * content only tells which order to check: the payment is read back from
 * HelloAsso before anything changes.
 */
class PaymentNotification extends Controller
{
    public function __invoke(Request $request, HelloAssoCheckout $checkout): Response
    {
        $order = TreeOrder::query()->find((int) $request->input('metadata.tree_order_id'));

        if ($order === null || $order->isPaid()) {
            return response()->noContent(200);
        }

        try {
            $checkout->confirm($order);
        } catch (HelloAssoUnavailable $exception) {
            report($exception);

            // HelloAsso sends the notification again later.
            return response()->noContent(503);
        }

        return response()->noContent(200);
    }
}
