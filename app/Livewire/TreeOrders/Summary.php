<?php

namespace App\Livewire\TreeOrders;

use App\TreeOrders\TreeOrdersSummary;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Trees to order from the producer.
 */
class Summary extends Component
{
    public function mount(): void
    {
        $this->authorize('manage-tree-orders');
    }

    public function render(TreeOrdersSummary $summary): View
    {
        return view('livewire.tree-orders.summary', ['summary' => $summary])
            ->title(__('trees.summary.title'));
    }
}
