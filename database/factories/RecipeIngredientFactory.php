<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipeIngredient>
 */
class RecipeIngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'name' => 'Farine',
            'quantity' => '500',
            'unit' => 'g',
            'orderable_key' => null,
            'grams_per_kg' => null,
            'position' => 0,
        ];
    }

    public function orderable(string $key, int $gramsPerKg): static
    {
        return $this->state(fn (array $attributes) => [
            'orderable_key' => $key,
            'grams_per_kg' => $gramsPerKg,
        ]);
    }
}
