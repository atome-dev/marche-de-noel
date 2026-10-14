<?php

namespace App\Http\Controllers\TreeOrders;

use App\Http\Controllers\Controller;
use App\Models\TreeOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * The family left the HelloAsso page, or the payment failed.
 */
class PaymentAborted extends Controller
{
    public function __invoke(TreeOrder $treeOrder): RedirectResponse
    {
        Gate::authorize('update', $treeOrder);

        return redirect()->route('trees.index')->with([
            'tree_status' => 'aborted',
            'tree_number' => $treeOrder->number,
        ]);
    }
}
