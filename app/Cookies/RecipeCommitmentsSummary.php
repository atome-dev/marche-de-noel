<?php

namespace App\Cookies;

use App\Models\Commitment;
use App\Models\Recipe;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Kilos of biscuits planned, recipe by recipe, grouped by biscuit family.
 *
 * Every recipe is listed, even without any commitment: the recipes nobody
 * chose stand out.
 */
class RecipeCommitmentsSummary
{
    /** @var Collection<string, list<array{recipe: Recipe, families: int, kilos: int}>> keyed by family name */
    private Collection $groups;

    private int $families;

    public function __construct()
    {
        $this->groups = Recipe::query()
            ->with('category')
            ->withCount('commitments')
            ->withSum('commitments', 'quantity_kg')
            ->orderBy('title')
            ->get()
            ->sortBy(fn (Recipe $recipe) => [$recipe->category->position ?? PHP_INT_MAX, $recipe->category->name ?? '', Str::lower(Str::ascii($recipe->title))])
            ->groupBy(fn (Recipe $recipe): string => $recipe->category->name ?? __('stats.cookies.no_category'))
            ->map(fn (Collection $recipes): array => $recipes->map(fn (Recipe $recipe): array => [
                'recipe' => $recipe,
                // One commitment per person and recipe: the count is the number of families.
                'families' => (int) $recipe->commitments_count,
                'kilos' => (int) $recipe->commitments_sum_quantity_kg,
            ])->values()->all());

        $this->families = Commitment::query()->distinct()->count('user_id');
    }

    /**
     * @return Collection<string, list<array{recipe: Recipe, families: int, kilos: int}>>
     */
    public function groups(): Collection
    {
        return $this->groups;
    }

    public function isEmpty(): bool
    {
        return $this->groups->isEmpty();
    }

    /**
     * Distinct families having planned at least one recipe.
     */
    public function families(): int
    {
        return $this->families;
    }

    public function kilos(): int
    {
        return (int) $this->groups->flatten(1)->sum('kilos');
    }

    public function recipesWithoutCommitment(): int
    {
        return $this->groups->flatten(1)->where('families', 0)->count();
    }
}
