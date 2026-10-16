<?php

namespace App\Http\Controllers\TreeOrders;

use App\Enums\TreeOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\TreeOrder;
use App\TreeOrders\HelloAsso\HelloAssoShop;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Payment of a pending order in the HelloAsso shop widget, embedded in the
 * page with the order to reproduce there.
 */
class ShopPayment extends Controller
{
    public function __invoke(TreeOrder $treeOrder, HelloAssoShop $shop): View|RedirectResponse
    {
        Gate::authorize('update', $treeOrder);

        if ($shop->widgetUrl() === null || $treeOrder->status() !== TreeOrderStatus::Pending) {
            return to_route('trees.index');
        }

        return view('tree-orders.shop-payment', [
            'order' => $treeOrder->load('lines'),
            'widgetUrl' => $shop->widgetUrl(),
        ]);
    }
}
