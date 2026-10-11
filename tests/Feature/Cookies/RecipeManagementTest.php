<?php

use App\Enums\StandType;
use App\Livewire\Cookies\Categories;
use App\Livewire\Cookies\RecipeForm;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\RecipeIngredient;
use App\Models\SchoolClass;
use App\Models\Stand;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    config(['marche.orders.ingredients' => ['Poudre d\'amande', 'Poudre de noisette']]);
    Storage::fake('public');

    $this->manager = User::factory()->create();
    Stand::factory()->ofType(StandType::Cookies)->create()->managers()->attach($this->manager);
});

describe('access', function () {
    test('volunteers cannot manage recipes', function () {
        $recipe = Recipe::factory()->create();

        $this->actingAs(User::factory()->create());

        $this->get(route('cookies.create'))->assertForbidden();
        $this->get(route('cookies.edit', $recipe))->assertForbidden();
        $this->get(route('cookies.categories'))->assertForbidden();
    });

    test('managers of other stands cannot manage recipes', function () {
        $other = User::factory()->create();
        Stand::factory()->create()->managers()->attach($other);

        $this->actingAs($other)->get(route('cookies.create'))->assertForbidden();
    });

    test('managers of the cookies stand and admins can', function () {
        $this->actingAs($this->manager)->get(route('cookies.create'))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('cookies.create'))->assertOk();
    });
});

describe('recipe form', function () {
    beforeEach(fn () => $this->actingAs($this->manager));

    test('a recipe is created with its ingredients, classes and links', function () {
        $category = RecipeCategory::factory()->create();
        $class = SchoolClass::query()->first();

        Livewire::test(RecipeForm::class)
            ->set('title', 'Croissants à la vanille')
            ->set('recipe_category_id', $category->id)
            ->set('schoolClassIds', [$class->id])
            ->set('instructions', '<p>Mélanger.</p><script>alert(1)</script>')
            ->set('video_url', 'https://youtu.be/dQw4w9WgXcQ')
            ->set('pdf_url', 'https://example.org/recette.pdf')
            ->set('ingredients', [
                ['name' => 'Œufs', 'quantity' => '3', 'unit' => '', 'orderable_key' => '', 'grams_per_kg' => ''],
                ['name' => 'Levure', 'quantity' => '1/2', 'unit' => 'sachet', 'orderable_key' => '', 'grams_per_kg' => ''],
                ['name' => 'Poudre d\'amande', 'quantity' => '200', 'unit' => 'g', 'orderable_key' => 'poudre-damande', 'grams_per_kg' => '250'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $recipe = Recipe::sole();

        expect($recipe->title)->toBe('Croissants à la vanille')
            ->and($recipe->category->is($category))->toBeTrue()
            ->and($recipe->schoolClasses->pluck('id')->all())->toBe([$class->id])
            ->and($recipe->instructions)->toBe('<p>Mélanger.</p>')
            ->and($recipe->ingredients->map(fn (RecipeIngredient $ingredient) => $ingredient->name.' — '.$ingredient->quantityLabel())->all())
            ->toBe(['Œufs — 3', 'Levure — 1/2 sachet', 'Poudre d\'amande — 200 g'])
            ->and($recipe->orderableGramsPerKg())->toBe(['poudre-damande' => 250]);
    });

    test('a recipe is updated and its ingredients replaced', function () {
        $recipe = Recipe::factory()->create(['title' => 'Ancien titre']);
        RecipeIngredient::factory()->for($recipe)->create(['name' => 'Farine']);

        Livewire::test(RecipeForm::class, ['recipe' => $recipe])
            ->assertSet('title', 'Ancien titre')
            ->assertSet('ingredients.0.name', 'Farine')
            ->set('title', 'Nouveau titre')
            ->call('addIngredient')
            ->set('ingredients.1.name', 'Sucre')
            ->call('removeIngredient', 0)
            ->call('save')
            ->assertHasNoErrors();

        expect($recipe->refresh()->title)->toBe('Nouveau titre')
            ->and($recipe->ingredients->pluck('name')->all())->toBe(['Sucre']);
    });

    test('fields are validated', function () {
        Livewire::test(RecipeForm::class)
            ->set('title', '')
            ->set('video_url', 'pas-une-adresse')
            ->set('pdf_url', 'pas-une-adresse')
            ->set('ingredients', [
                ['name' => '', 'quantity' => '3', 'unit' => '', 'orderable_key' => '', 'grams_per_kg' => ''],
                ['name' => 'Amande', 'quantity' => '', 'unit' => '', 'orderable_key' => 'poudre-damande', 'grams_per_kg' => ''],
                ['name' => 'Autre', 'quantity' => '', 'unit' => '', 'orderable_key' => 'inconnu', 'grams_per_kg' => '10'],
            ])
            ->call('save')
            ->assertHasErrors(['title', 'video_url', 'pdf_url', 'ingredients.0.name', 'ingredients.1.grams_per_kg', 'ingredients.2.orderable_key']);

        expect(Recipe::count())->toBe(0);
    });

    test('photos and a pdf can be uploaded, then removed', function () {
        $component = Livewire::test(RecipeForm::class)
            ->set('title', 'Sablés')
            ->set('newPhotos', [UploadedFile::fake()->image('un.jpg'), UploadedFile::fake()->image('deux.png')])
            ->set('pdfFile', UploadedFile::fake()->create('recette.pdf', 120, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $recipe = Recipe::sole();

        expect($recipe->photos)->toHaveCount(2)
            ->and($recipe->pdf_path)->not->toBeNull();
        Storage::disk('public')->assertExists($recipe->photos->first()->path);
        Storage::disk('public')->assertExists($recipe->pdf_path);

        $photo = $recipe->photos->first();
        $pdfPath = $recipe->pdf_path;

        Livewire::test(RecipeForm::class, ['recipe' => $recipe])
            ->call('deletePhoto', $photo->id)
            ->call('removePdf');

        expect($recipe->refresh()->photos)->toHaveCount(1)
            ->and($recipe->pdf_path)->toBeNull();
        Storage::disk('public')->assertMissing($photo->path);
        Storage::disk('public')->assertMissing($pdfPath);
    });

    test('only images and pdf files are accepted', function () {
        Livewire::test(RecipeForm::class)
            ->set('title', 'Sablés')
            ->set('newPhotos', [UploadedFile::fake()->create('virus.exe', 10)])
            ->set('pdfFile', UploadedFile::fake()->image('photo.jpg'))
            ->call('save')
            ->assertHasErrors(['newPhotos.0', 'pdfFile']);
    });

    test('deleting a recipe removes its files', function () {
        Livewire::test(RecipeForm::class)
            ->set('title', 'Sablés')
            ->set('newPhotos', [UploadedFile::fake()->image('un.jpg')])
            ->call('save');

        $recipe = Recipe::sole();
        $path = $recipe->photos->first()->path;

        Livewire::test(RecipeForm::class, ['recipe' => $recipe])
            ->call('delete')
            ->assertRedirect(route('cookies.index'));

        expect(Recipe::count())->toBe(0);
        Storage::disk('public')->assertMissing($path);
    });
});

describe('biscuit families', function () {
    beforeEach(fn () => $this->actingAs($this->manager));

    test('can be added, renamed and deleted', function () {
        $component = Livewire::test(Categories::class)
            ->set('name', 'Sablés')
            ->call('add')
            ->assertHasNoErrors();

        $category = RecipeCategory::sole();
        $recipe = Recipe::factory()->create(['recipe_category_id' => $category->id]);

        $component->call('rename', $category->id, 'Sablés et butterbredele');

        expect($category->refresh()->name)->toBe('Sablés et butterbredele');

        $component->call('delete', $category->id);

        expect(RecipeCategory::count())->toBe(0)
            ->and($recipe->refresh()->recipe_category_id)->toBeNull();
    });

    test('must have a unique name', function () {
        RecipeCategory::factory()->create(['name' => 'Sablés']);

        Livewire::test(Categories::class)
            ->set('name', 'Sablés')
            ->call('add')
            ->assertHasErrors(['name']);
    });
});
