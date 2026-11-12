<?php

namespace Database\Factories;

use App\Models\Score;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Score>
 */
class ScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Partition '.fake()->unique()->numberBetween(1, 99999),
            'path' => Score::DIRECTORY.'/'.fake()->uuid().'.pdf',
            'size' => 120_000,
            'position' => (int) Score::max('position') + 1,
        ];
    }
}
