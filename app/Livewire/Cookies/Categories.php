<?php

namespace App\Livewire\Cookies;

use App\Models\RecipeCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Families of biscuits, used to keep the recipes diverse.
 *
 * @property-read Collection<int, RecipeCategory> $categories
 */
class Categories extends Component
{
    public string $name = '';

    public function mount(): void
    {
        $this->authorize('manage-cookies');
    }

    public function add(): void
    {
        $this->authorize('manage-cookies');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique(RecipeCategory::class, 'name')],
        ]);

        RecipeCategory::create([
            'name' => $validated['name'],
            'position' => (int) RecipeCategory::max('position') + 1,
        ]);

        $this->reset('name');
        unset($this->categories);
    }

    public function rename(int $categoryId, string $name): void
    {
        $this->authorize('manage-cookies');

        $category = RecipeCategory::findOrFail($categoryId);

        $validated = Validator::make(['name' => trim($name)], [
            'name' => ['required', 'string', 'max:100', Rule::unique(RecipeCategory::class, 'name')->ignore($category->id)],
        ])->validate();

        $category->update($validated);

        unset($this->categories);
    }

    public function delete(int $categoryId): void
    {
        $this->authorize('manage-cookies');

        RecipeCategory::findOrFail($categoryId)->delete();

        unset($this->categories);
    }

    /**
     * @return Collection<int, RecipeCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return RecipeCategory::query()->ordered()->withCount('recipes')->get();
    }

    public function render(): View
    {
        return view('livewire.cookies.categories')
            ->title(__('cookies.categories.title'));
    }
}
