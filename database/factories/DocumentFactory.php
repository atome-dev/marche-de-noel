<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Document '.fake()->unique()->numberBetween(1, 99999),
            'path' => Document::DIRECTORY.'/'.fake()->uuid().'.pdf',
            'size' => 120_000,
            'position' => (int) Document::max('position') + 1,
        ];
    }
}
