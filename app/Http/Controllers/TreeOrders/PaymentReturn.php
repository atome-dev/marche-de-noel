<?php

namespace App\Http\Controllers\TreeOrders;

use App\Exceptions\HelloAssoUnavailable;
use App\Http\Controllers\Controller;
use App\Models\TreeOrder;
use App\TreeOrders\HelloAsso\HelloAssoCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * The family comes back from HelloAsso after paying.
 */
class PaymentReturn extends Controller
{
    public function __invoke(TreeOrder $treeOrder, HelloAssoCheckout $checkout): RedirectResponse
    {
        Gate::authorize('update', $treeOrder);

        try {
            $paid = $checkout->confirm($treeOrder);
        } catch (HelloAssoUnavailable $exception) {
            report($exception);
            $paid = false;
        }

        return redirect()->route('trees.index')->with([
            'tree_status' => $paid ? 'paid' : 'pending',
            'tree_number' => $treeOrder->number,
        ]);
    }
}
