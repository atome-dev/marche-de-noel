<?php

use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['marche.orders.ingredients' => ['Poudre d\'amande', 'Poudre de noisette', 'Noix de coco râpée']]);

    Storage::fake('public');

    // Stand-in for resources/recettes, with the files of a single recipe.
    $this->source = sys_get_temp_dir().'/recettes-'.uniqid();
    mkdir($this->source);
    file_put_contents($this->source.'/amaretti.pdf', '%PDF-1.4 amaretti');
    file_put_contents($this->source.'/amaretti.source.png', 'png');

    config(['marche.recipes_source' => $this->source]);
});

afterEach(function () {
    array_map('unlink', glob($this->source.'/*') ?: []);
    rmdir($this->source);
});

test('the reference data fits the database columns', function () {
    foreach (require database_path('data/recipes.php') as $recipe) {
        expect(mb_strlen($recipe['title']))->toBeLessThanOrEqual(255);

        foreach ($recipe['ingredients'] as $line) {
            [$quantity, $unit, $name, $key] = array_pad(array_map('trim', explode('|', $line)), 4, '');

            expect(mb_strlen($name))->toBeGreaterThan(0)->toBeLessThanOrEqual(255, $name)
                ->and(mb_strlen($quantity))->toBeLessThanOrEqual(20, $line)
                ->and(mb_strlen($unit))->toBeLessThanOrEqual(30, $line)
                ->and(mb_strlen($key))->toBeLessThanOrEqual(100, $line);
        }
    }

    foreach (array_keys(require database_path('data/stands.php')) as $name) {
        expect(mb_strlen($name))->toBeLessThanOrEqual(255);
    }
});

test('the reference recipes are imported with their families and classes', function () {
    $this->artisan('marche:import-recipes')->assertSuccessful();

    expect(Recipe::count())->toBe(21)
        ->and(RecipeCategory::query()->ordered()->pluck('name')->all())->toBe(['Recette facile', 'Recette élaborée']);

    $macarons = Recipe::firstWhere('title', 'Macarons aux deux chocolats');

    expect($macarons->category->name)->toBe('Recette élaborée')
        ->and($macarons->schoolClasses->pluck('name')->all())->toBe(['JE Parc', '3ème Classe', '11ème Classe']);
});

test('ingredients keep quantity and unit apart, and orderable ones get their proportion', function () {
    $this->artisan('marche:import-recipes')->assertSuccessful();

    $amaretti = Recipe::firstWhere('title', 'Amaretti');
    $ingredients = $amaretti->ingredients->keyBy('name');

    expect($ingredients['Farine à pâtisserie']->quantityLabel())->toBe('3 c. à soupe')
        ->and($ingredients['Blancs d’œufs']->quantityLabel())->toBe('6')
        ->and($ingredients['Blancs d’œufs']->unit)->toBeNull()
        ->and($amaretti->orderableGramsPerKg())->toBe(['poudre-damande' => 360])
        ->and($amaretti->instructions)->toContain('<ol>');
});

test('orderable links are skipped when the ingredient is not orderable this year', function () {
    config(['marche.orders.ingredients' => ['Poudre de noisette']]);

    $this->artisan('marche:import-recipes')->assertSuccessful();

    expect(Recipe::firstWhere('title', 'Amaretti')->orderableGramsPerKg())->toBe([])
        ->and(Recipe::firstWhere('title', 'Macarons aux noisettes')->orderableGramsPerKg())->toBe(['poudre-de-noisette' => 750]);
});

test('the pdf and the photo are copied when they exist', function () {
    $this->artisan('marche:import-recipes')->assertSuccessful();

    $amaretti = Recipe::firstWhere('title', 'Amaretti');

    expect($amaretti->pdf_path)->toBe('recipes/'.$amaretti->id.'/amaretti.pdf')
        ->and($amaretti->photos)->toHaveCount(1);

    Storage::disk('public')->assertExists($amaretti->pdf_path);
    Storage::disk('public')->assertExists($amaretti->photos->first()->path);

    expect(Storage::disk('public')->get($amaretti->pdf_path))->toBe('%PDF-1.4 amaretti')
        ->and(is_file($this->source.'/amaretti.pdf'))->toBeTrue();

    $granola = Recipe::firstWhere('title', 'Biscuits granola');

    expect($granola->pdf_path)->toBeNull()
        ->and($granola->photos)->toBeEmpty();
});

test('missing files are reported without stopping the import', function () {
    $this->artisan('marche:import-recipes')
        ->expectsOutputToContain('biscuits-granola.pdf')
        ->assertSuccessful();

    expect(Recipe::firstWhere('title', 'Biscuits granola'))->not->toBeNull();
});

test('every class of the reference recipes exists', function () {
    $known = SchoolClass::query()->pluck('name')->all();

    foreach (require database_path('data/recipes.php') as $recipe) {
        expect($recipe['classes'])->not->toBeEmpty()
            ->and(array_diff($recipe['classes'], $known))->toBe([], $recipe['title']);
    }

    $this->artisan('marche:import-recipes')->assertSuccessful();

    expect(Recipe::firstWhere('title', 'Pain d’épices de Nuremberg')->schoolClasses->pluck('name')->all())->toBe(['JE Balcon', '13ème Classe'])
        ->and(Recipe::firstWhere('title', 'Petits sablés à l’orange')->schoolClasses->pluck('name')->all())->toBe(['JE Balcon']);
});

test('the import can be run again without duplicating nor overwriting recipes', function () {
    $this->artisan('marche:import-recipes')->assertSuccessful();

    Recipe::firstWhere('title', 'Amaretti')->update(['instructions' => '<p>Modifié</p>']);

    $this->artisan('marche:import-recipes')->assertSuccessful();

    expect(Recipe::count())->toBe(21)
        ->and(RecipeCategory::count())->toBe(2)
        ->and(Recipe::firstWhere('title', 'Amaretti')->instructions)->toBe('<p>Modifié</p>');
});
