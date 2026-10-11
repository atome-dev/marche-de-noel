<?php

namespace Database\Factories;

use App\Enums\StandType;
use App\Models\Stand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stand>
 */
class StandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'description' => null,
            'type' => StandType::Generic,
            'is_active' => true,
            'is_open_to_all' => false,
            'position' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function ofType(StandType $type): static
    {
        return $this->state(fn (array $attributes) => ['type' => $type]);
    }
}
