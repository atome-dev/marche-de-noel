<?php

namespace App\Console\Commands;

use App\Cookies\OrderableIngredients;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\SchoolClass;
use App\Support\HtmlSanitizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Signature('marche:import-recipes')]
#[Description('Crée les recettes de la liste de référence (database/data/recipes.php) avec leurs fichiers de resources/recettes')]
class ImportRecipes extends Command
{
    /** @var list<string> */
    private array $warnings = [];

    public function handle(OrderableIngredients $orderables): int
    {
        $created = 0;
        $classes = SchoolClass::query()->pluck('id', 'name');

        foreach (require database_path('data/recipes.php') as $data) {
            // Existing recipes are left untouched: managers may have changed them.
            if (Recipe::where('title', $data['title'])->exists()) {
                continue;
            }

            $recipe = DB::transaction(function () use ($data, $classes, $orderables): Recipe {
                $category = RecipeCategory::firstOrCreate(
                    ['name' => $data['category']],
                    ['position' => (int) RecipeCategory::max('position') + 1],
                );

                $recipe = Recipe::create([
                    'recipe_category_id' => $category->id,
                    'title' => $data['title'],
                    'instructions' => HtmlSanitizer::clean($data['instructions']),
                ]);

                $recipe->schoolClasses()->sync($this->classIds($data, $classes->all()));

                foreach ($data['ingredients'] as $position => $line) {
                    $recipe->ingredients()->create($this->ingredient($line, $position, $orderables));
                }

                return $recipe;
            });

            $this->copyFiles($recipe, $data);
            $created++;
        }

        foreach ($this->warnings as $warning) {
            $this->warn($warning);
        }

        $this->info(trans_choice('commands.import_recipes.done', $created, ['count' => $created]));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $classes
     * @return list<int>
     */
    private function classIds(array $data, array $classes): array
    {
        $ids = [];

        foreach ($data['classes'] as $name) {
            if (isset($classes[$name])) {
                $ids[] = $classes[$name];
            } else {
                $this->warnings[] = __('commands.import_recipes.unknown_class', ['class' => $name, 'recipe' => $data['title']]);
            }
        }

        return $ids;
    }

    /**
     * "quantity | unit | name | orderable key" => ingredient attributes.
     *
     * @return array<string, mixed>
     */
    private function ingredient(string $line, int $position, OrderableIngredients $orderables): array
    {
        [$quantity, $unit, $name, $orderableKey] = array_pad(array_map('trim', explode('|', $line)), 4, '');

        $isOrderable = $orderableKey !== '' && $orderables->has($orderableKey) && (int) $quantity > 0;

        return [
            'name' => $name,
            'quantity' => $quantity !== '' ? $quantity : null,
            'unit' => $unit !== '' ? $unit : null,
            'orderable_key' => $isOrderable ? $orderableKey : null,
            // The PDF recipes are written for 1 kg of biscuits.
            'grams_per_kg' => $isOrderable ? (int) $quantity : null,
            'position' => $position,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function copyFiles(Recipe $recipe, array $data): void
    {
        $disk = Storage::disk('public');
        $directory = 'recipes/'.$recipe->id;
        $sourceDirectory = rtrim(config('marche.recipes_source'), '/');

        if ($data['pdf'] !== null) {
            $source = $sourceDirectory.'/'.$data['pdf'];

            if (is_file($source)) {
                $disk->put($directory.'/'.$data['pdf'], (string) file_get_contents($source));
                $recipe->update(['pdf_path' => $directory.'/'.$data['pdf']]);
            } else {
                $this->warnings[] = __('commands.import_recipes.missing_file', ['file' => $data['pdf'], 'recipe' => $data['title']]);
            }
        }

        if ($data['photo'] !== null) {
            $source = $sourceDirectory.'/'.$data['photo'];

            if (is_file($source)) {
                $disk->put($directory.'/'.$data['photo'], (string) file_get_contents($source));
                $recipe->photos()->create(['path' => $directory.'/'.$data['photo'], 'position' => 1]);
            } else {
                $this->warnings[] = __('commands.import_recipes.missing_file', ['file' => $data['photo'], 'recipe' => $data['title']]);
            }
        }
    }
}
