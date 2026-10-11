<?php

namespace App\Livewire\Cookies;

use App\Cookies\OrderableIngredients;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\SchoolClass;
use App\Support\HtmlSanitizer;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Recipe editor for the managers of the cookies stand and the admins.
 *
 * @property-read Recipe|null $recipe
 * @property-read Collection<int, RecipeCategory> $categories
 * @property-read Collection<int, SchoolClass> $schoolClasses
 * @property-read array<string, string> $orderables
 */
class RecipeForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $recipeId = null;

    public string $title = '';

    public ?int $recipe_category_id = null;

    /** @var array<int, int|string> */
    public array $schoolClassIds = [];

    public string $instructions = '';

    public string $video_url = '';

    public string $pdf_url = '';

    /** @var TemporaryUploadedFile|null */
    public $pdfFile = null;

    /** @var array<int, TemporaryUploadedFile> */
    public array $newPhotos = [];

    /**
     * Rows edited in the browser: name, quantity, unit, orderable_key, grams_per_kg.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $ingredients = [];

    public function mount(?Recipe $recipe = null): void
    {
        $this->authorize('manage-cookies');

        if ($recipe === null || ! $recipe->exists) {
            $this->addIngredient();

            return;
        }

        $this->recipeId = $recipe->id;
        $this->title = $recipe->title;
        $this->recipe_category_id = $recipe->recipe_category_id;
        $this->schoolClassIds = $recipe->schoolClasses->pluck('id')->all();
        $this->instructions = (string) $recipe->instructions;
        $this->video_url = (string) $recipe->video_url;
        $this->pdf_url = (string) $recipe->pdf_url;
        $this->ingredients = $recipe->ingredients->map(fn ($ingredient): array => [
            'name' => $ingredient->name,
            'quantity' => (string) $ingredient->quantity,
            'unit' => (string) $ingredient->unit,
            'orderable_key' => (string) $ingredient->orderable_key,
            'grams_per_kg' => (string) $ingredient->grams_per_kg,
        ])->all();
    }

    public function addIngredient(): void
    {
        $this->ingredients[] = ['name' => '', 'quantity' => '', 'unit' => '', 'orderable_key' => '', 'grams_per_kg' => ''];
    }

    public function removeIngredient(int $index): void
    {
        unset($this->ingredients[$index]);

        $this->ingredients = array_values($this->ingredients);
    }

    public function save(): void
    {
        $this->authorize('manage-cookies');

        // Rows left completely empty are ignored.
        $this->ingredients = array_values(array_filter(
            $this->ingredients,
            fn (array $ingredient): bool => filled($ingredient['name'] ?? '') || filled($ingredient['quantity'] ?? '')
                || filled($ingredient['unit'] ?? '') || filled($ingredient['orderable_key'] ?? ''),
        ));

        $validated = $this->validate($this->rules());

        $recipe = $this->recipe ?? new Recipe;
        $isNew = ! $recipe->exists;

        DB::transaction(function () use ($recipe, $validated): void {
            $recipe->fill([
                'title' => $validated['title'],
                'recipe_category_id' => $validated['recipe_category_id'] ?: null,
                'instructions' => HtmlSanitizer::clean($validated['instructions']) ?: null,
                'video_url' => $validated['video_url'] ?: null,
                'pdf_url' => $validated['pdf_url'] ?: null,
            ]);
            $recipe->save();

            $recipe->schoolClasses()->sync($validated['schoolClassIds'] ?? []);

            $recipe->ingredients()->delete();

            foreach (array_values($validated['ingredients'] ?? []) as $position => $ingredient) {
                $recipe->ingredients()->create([
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity'] ?: null,
                    'unit' => $ingredient['unit'] ?: null,
                    'orderable_key' => $ingredient['orderable_key'] ?: null,
                    'grams_per_kg' => $ingredient['orderable_key'] ? (int) $ingredient['grams_per_kg'] : null,
                    'position' => $position,
                ]);
            }
        });

        $this->storeFiles($recipe);

        if ($isNew) {
            session()->flash('status', __('cookies.form.created'));

            $this->redirectRoute('cookies.edit', $recipe, navigate: true);

            return;
        }

        unset($this->recipe);

        Flux::toast(variant: 'success', text: __('cookies.form.saved'));
    }

    public function deletePhoto(int $photoId): void
    {
        $this->authorize('manage-cookies');

        $photo = $this->existingRecipe()->photos()->findOrFail($photoId);

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        unset($this->recipe);
    }

    public function removePdf(): void
    {
        $this->authorize('manage-cookies');

        $recipe = $this->existingRecipe();

        if ($recipe->pdf_path) {
            Storage::disk('public')->delete($recipe->pdf_path);
            $recipe->update(['pdf_path' => null]);
        }

        unset($this->recipe);
    }

    public function delete(): void
    {
        $this->authorize('manage-cookies');

        $recipe = $this->existingRecipe();

        Storage::disk('public')->deleteDirectory('recipes/'.$recipe->id);
        $recipe->delete();

        session()->flash('status', __('cookies.form.deleted', ['title' => $recipe->title]));

        $this->redirectRoute('cookies.index', navigate: true);
    }

    #[Computed]
    public function recipe(): ?Recipe
    {
        return $this->recipeId ? Recipe::with('photos')->findOrFail($this->recipeId) : null;
    }

    /**
     * @return Collection<int, RecipeCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return RecipeCategory::query()->ordered()->get();
    }

    /**
     * @return Collection<int, SchoolClass>
     */
    #[Computed]
    public function schoolClasses(): Collection
    {
        return SchoolClass::query()->ordered()->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function orderables(): array
    {
        return app(OrderableIngredients::class)->all();
    }

    public function render(): View
    {
        return view('livewire.cookies.recipe-form')
            ->title($this->recipeId ? $this->title : __('cookies.form.create_title'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'recipe_category_id' => ['nullable', 'integer', Rule::exists(RecipeCategory::class, 'id')],
            'schoolClassIds' => ['array'],
            'schoolClassIds.*' => ['integer', Rule::exists(SchoolClass::class, 'id')],
            'instructions' => ['nullable', 'string', 'max:20000'],
            'video_url' => ['nullable', 'url:http,https', 'max:500'],
            'pdf_url' => ['nullable', 'url:http,https', 'max:500'],
            'pdfFile' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'newPhotos' => ['array', 'max:10'],
            'newPhotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'ingredients' => ['array', 'max:40'],
            'ingredients.*.name' => ['required', 'string', 'max:255'],
            'ingredients.*.quantity' => ['nullable', 'string', 'max:20'],
            'ingredients.*.unit' => ['nullable', 'string', 'max:30'],
            'ingredients.*.orderable_key' => ['nullable', 'string', Rule::in(array_keys($this->orderables))],
            'ingredients.*.grams_per_kg' => ['nullable', 'required_with:ingredients.*.orderable_key', 'integer', 'min:1', 'max:5000'],
        ];
    }

    private function storeFiles(Recipe $recipe): void
    {
        $position = (int) $recipe->photos()->max('position');

        foreach ($this->newPhotos as $photo) {
            $recipe->photos()->create([
                'path' => $photo->store('recipes/'.$recipe->id, 'public'),
                'position' => ++$position,
            ]);
        }

        if ($this->pdfFile !== null) {
            if ($recipe->pdf_path) {
                Storage::disk('public')->delete($recipe->pdf_path);
            }

            $recipe->update(['pdf_path' => $this->pdfFile->store('recipes/'.$recipe->id, 'public')]);
        }

        $this->reset('newPhotos', 'pdfFile');
    }

    private function existingRecipe(): Recipe
    {
        abort_if($this->recipe === null, 404);

        return $this->recipe;
    }
}
