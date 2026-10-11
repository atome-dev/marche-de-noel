<?php

namespace App\Livewire\Cookies;

use App\Cookies\CookieOrdering;
use App\Cookies\OrdersSummary;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Purchasing summary of the ingredient orders.
 */
class Orders extends Component
{
    public function mount(): void
    {
        $this->authorize('manage-cookies');
    }

    public function render(OrdersSummary $summary, CookieOrdering $ordering): View
    {
        return view('livewire.cookies.orders', [
            'summary' => $summary,
            'deadline' => $ordering->deadline(),
            'locked' => $ordering->isLocked(),
        ])->title(__('cookies.orders.title'));
    }
}
