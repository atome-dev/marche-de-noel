<?php

namespace App\Http\Controllers\TreeOrders;

use App\Concert\ConcertTickets;
use App\Exceptions\HelloAssoUnavailable;
use App\Http\Controllers\Controller;
use App\Models\TreeOrder;
use App\TreeOrders\HelloAsso\HelloAssoCheckout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Server notification of HelloAsso (declared in its back office). Its
 * content only tells which order to check: the payment is read back from
 * HelloAsso before anything changes. A notification of the concert
 * ticketing only refreshes its tickets.
 */
class PaymentNotification extends Controller
{
    public function __invoke(Request $request, HelloAssoCheckout $checkout, ConcertTickets $concertTickets): Response
    {
        // Event ticketing (the concert): the tickets are read again on next display.
        if ($request->input('data.formType') === 'Event') {
            if ($concertTickets->isFormSlug($request->input('data.formSlug'))) {
                $concertTickets->forget();
            }

            return response()->noContent(200);
        }

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
