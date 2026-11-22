<?php

namespace App\Livewire\Stats;

use App\Planning\OpenPlacesSummary;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Statistics for administrators and stand managers: places still to fill.
 */
class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('see-stats');
    }

    public function render(): View
    {
        $user = auth()->user();

        // Administrators see every stand, managers only their own.
        $standIds = $user->isAdmin() ? null : $user->managedStands()->pluck('stands.id')->all();

        return view('livewire.stats.index', [
            'summary' => new OpenPlacesSummary($standIds),
        ])->title(__('stats.title'));
    }
}
