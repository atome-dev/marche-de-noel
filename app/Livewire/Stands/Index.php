<?php

namespace App\Livewire\Stands;

use App\Models\Stand;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Collection<int, Stand> $stands
 */
class Index extends Component
{
    /**
     * Drag and drop (administrators): the position is the zero-based index
     * in the list. The order is the one of the planning.
     */
    public function reorder(int $standId, int $position): void
    {
        $this->authorize('admin');

        DB::transaction(function () use ($standId, $position): void {
            $ids = Stand::query()->ordered()->pluck('id')->reject(fn (int $id) => $id === $standId)->values()->all();

            array_splice($ids, max(0, min($position, count($ids))), 0, [Stand::findOrFail($standId)->id]);

            foreach ($ids as $index => $id) {
                Stand::whereKey($id)->update(['position' => $index + 1]);
            }
        });

        unset($this->stands);
    }

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
