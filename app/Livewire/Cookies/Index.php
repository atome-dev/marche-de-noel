<?php

namespace App\Livewire\Cookies;

use App\Models\Recipe;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * "Les petits gâteaux": recipes of the user's classes first, then the others.
 * Managers of the cookies stand also get the kilos announced for each recipe.
 *
 * @property-read Collection<int, Recipe> $recipes
 * @property-read Collection<int, Recipe> $mine
 * @property-read Collection<int, Recipe> $others
 */
class Index extends Component
{
    /**
     * @return Collection<int, Recipe>
     */
    #[Computed]
    public function recipes(): Collection
    {
        return Recipe::query()
            ->with(['category', 'schoolClasses', 'photos'])
            ->withExists(['commitments as is_committed' => fn ($query) => $query->where('user_id', Auth::id())])
            ->when(Gate::allows('manage-cookies'), fn ($query) => $query->withSum('commitments as total_kg', 'quantity_kg'))
            ->orderBy('title')
            ->get()
            ->sortBy(fn (Recipe $recipe) => [$recipe->category->position ?? PHP_INT_MAX, $recipe->category->name ?? '', $recipe->title])
            ->values();
    }

    /**
     * Recipes linked to one of the user's classes.
     *
     * @return Collection<int, Recipe>
     */
    #[Computed]
    public function mine(): Collection
    {
        $classIds = Auth::user()->schoolClasses->pluck('id');

        return $this->recipes
            ->filter(fn (Recipe $recipe) => $recipe->schoolClasses->pluck('id')->intersect($classIds)->isNotEmpty())
            ->values();
    }

    /**
     * @return Collection<int, Recipe>
     */
    #[Computed]
    public function others(): Collection
    {
        return $this->recipes->diff($this->mine)->values();
    }

    public function render(): View
    {
        return view('livewire.cookies.index')
            ->title(__('cookies.title'));
    }
}
