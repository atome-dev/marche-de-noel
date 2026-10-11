<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_category_id' => RecipeCategory::factory(),
            'title' => 'Recette '.fake()->unique()->numberBetween(1, 99999),
            'instructions' => '<p>'.fake()->sentence().'</p>',
            'pdf_url' => null,
            'pdf_path' => null,
            'video_url' => null,
        ];
    }
}
