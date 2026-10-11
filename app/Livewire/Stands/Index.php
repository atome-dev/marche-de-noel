<?php

namespace App\Livewire\Stands;

use App\Models\Stand;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    /**
     * @return Collection<int, Stand>
     */
    #[Computed]
    public function stands(): Collection
    {
        $user = Auth::user();

        return Stand::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->whereHas('managers', fn ($managers) => $managers->whereKey($user->id)))
            ->with(['managers', 'schoolClasses'])
            ->withCount('slots')
            ->withSum('slots', 'capacity')
            ->ordered()
            ->get();
    }

    public function render(): View
    {
        return view('livewire.stands.index')
            ->title(__('stands.index.title'));
    }
}
