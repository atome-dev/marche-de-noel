<?php

namespace Database\Factories;

use App\Models\Registration;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slot_id' => Slot::factory(),
            'user_id' => User::factory(),
            'guest_name' => null,
            'message' => null,
        ];
    }

    public function guest(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'guest_name' => $name,
        ]);
    }
}
