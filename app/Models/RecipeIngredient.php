<?php

namespace App\Models;

use App\Cookies\OrderableIngredients;
use Carbon\CarbonImmutable;
use Database\Factories\RecipeIngredientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $recipe_id
 * @property string $name
 * @property string|null $quantity
 * @property string|null $unit
 * @property string|null $orderable_key
 * @property int|null $grams_per_kg
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Recipe $recipe
 */
#[Fillable(['name', 'quantity', 'unit', 'orderable_key', 'grams_per_kg', 'position'])]
class RecipeIngredient extends Model
{
    /** @use HasFactory<RecipeIngredientFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grams_per_kg' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Linked to an ingredient that is still orderable this year, with a proportion.
     */
    public function isOrderable(): bool
    {
        return $this->orderable_key !== null
            && $this->grams_per_kg > 0
            && app(OrderableIngredients::class)->has($this->orderable_key);
    }

    /**
     * "500 g", "1/2 sachet", "3"…
     */
    public function quantityLabel(): string
    {
        return trim($this->quantity.' '.$this->unit);
    }
}
