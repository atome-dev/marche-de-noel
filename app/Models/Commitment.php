<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CommitmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A family plans to bake a number of kilos of a recipe.
 *
 * @property int $id
 * @property int $user_id
 * @property int $recipe_id
 * @property int $quantity_kg
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Recipe $recipe
 * @property-read Collection<int, IngredientOrder> $orders
 */
#[Fillable(['user_id', 'recipe_id', 'quantity_kg'])]
class Commitment extends Model
{
    /** @use HasFactory<CommitmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_kg' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * @return HasMany<IngredientOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(IngredientOrder::class);
    }
}
