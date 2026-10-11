<?php

namespace Database\Factories;

use App\Models\Commitment;
use App\Models\IngredientOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IngredientOrder>
 */
class IngredientOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commitment_id' => Commitment::factory(),
            'orderable_key' => 'poudre-d-amande',
            'grams' => 200,
        ];
    }
}
