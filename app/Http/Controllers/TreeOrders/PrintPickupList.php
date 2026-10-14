<?php

namespace App\Http\Controllers\TreeOrders;

use App\Http\Controllers\Controller;
use App\Models\TreeOrder;
use Illuminate\Contracts\View\View;

/**
 * Paper list of the paid orders, in case the network fails on pickup day.
 */
class PrintPickupList extends Controller
{
    public function __invoke(): View
    {
        return view('tree-orders.pickup-print', [
            'orders' => TreeOrder::query()->counted()->with('lines')->orderBy('customer_name')->get(),
        ]);
    }
}
