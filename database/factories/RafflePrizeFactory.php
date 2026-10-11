<?php

namespace Database\Factories;

use App\Models\RafflePrize;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RafflePrize>
 */
class RafflePrizeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => 'Lot '.fake()->unique()->numberBetween(1, 99999),
            'position' => (int) RafflePrize::max('position') + 1,
        ];
    }
}
