<?php

namespace App\Livewire\TreeOrders;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Christmas tree order page of the families.
 */
class OrderForm extends Component
{
    public function render(): View
    {
        return view('livewire.tree-orders.order-form')
            ->title(__('trees.title'));
    }
}
