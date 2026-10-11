<?php

namespace Database\Factories;

use App\Models\Slot;
use App\Models\Stand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slot>
 */
class SlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stand_id' => Stand::factory(),
            'date' => '2026-11-28',
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'capacity' => 2,
            'label' => null,
            'is_reserved' => false,
            'reserved_emails' => null,
        ];
    }

    public function at(string $date, string $startsAt, string $endsAt): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }

    /**
     * @param  list<string>  $emails
     */
    public function reservedFor(array $emails): static
    {
        return $this->state(fn (array $attributes) => [
            'is_reserved' => true,
            'reserved_emails' => $emails,
        ]);
    }
}
