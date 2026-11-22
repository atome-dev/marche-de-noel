<?php

namespace App\Livewire\Stats;

use App\Cookies\RecipeCommitmentsSummary;
use App\Planning\OpenPlacesSummary;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Statistics for administrators and stand managers, in tabs: places still to
 * fill on the planning, and biscuits planned (cookies managers only).
 */
class Index extends Component
{
    public const TAB_PLANNING = 'planning';

    public const TAB_COOKIES = 'petits-gateaux';

    #[Url(as: 'onglet', except: self::TAB_PLANNING)]
    public string $tab = self::TAB_PLANNING;

    public function mount(): void
    {
        $this->authorize('see-stats');

        $this->selectTab($this->tab);
    }

    /**
     * Tabs the user may open, in display order.
     *
     * @return list<string>
     */
    public function tabs(): array
    {
        return array_values(array_filter([
            self::TAB_PLANNING,
            auth()->user()->can('manage-cookies') ? self::TAB_COOKIES : null,
        ]));
    }

    /**
     * An unknown or forbidden tab falls back to the planning.
     */
    public function selectTab(string $tab): void
    {
        $this->tab = in_array($tab, $this->tabs(), true) ? $tab : self::TAB_PLANNING;
    }

    public function render(): View
    {
        return view('livewire.stats.index', [
            'tabs' => $this->tabs(),
            'openPlaces' => $this->tab === self::TAB_PLANNING ? $this->openPlaces() : null,
            'cookies' => $this->tab === self::TAB_COOKIES ? new RecipeCommitmentsSummary : null,
        ])->title(__('stats.title'));
    }

    /**
     * Administrators see every stand, managers only their own.
     */
    private function openPlaces(): OpenPlacesSummary
    {
        $user = auth()->user();

        return new OpenPlacesSummary($user->isAdmin() ? null : $user->managedStands()->pluck('stands.id')->all());
    }
}
