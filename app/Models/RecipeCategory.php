<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RecipeCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A family of biscuits, used to keep the recipes diverse.
 *
 * @property int $id
 * @property string $name
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Recipe> $recipes
 */
#[Fillable(['name', 'position'])]
class RecipeCategory extends Model
{
    /** @use HasFactory<RecipeCategoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<Recipe, $this>
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    /**
     * @param  Builder<RecipeCategory>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('name');
    }
}
