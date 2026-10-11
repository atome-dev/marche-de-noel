<?php

namespace App\Models;

use App\Support\YouTube;
use Carbon\CarbonImmutable;
use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int|null $recipe_category_id
 * @property string $title
 * @property string|null $instructions
 * @property string|null $pdf_url
 * @property string|null $pdf_path
 * @property string|null $video_url
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read RecipeCategory|null $category
 * @property-read Collection<int, SchoolClass> $schoolClasses
 * @property-read Collection<int, RecipeIngredient> $ingredients
 * @property-read Collection<int, RecipePhoto> $photos
 * @property-read Collection<int, Commitment> $commitments
 */
#[Fillable(['recipe_category_id', 'title', 'instructions', 'pdf_url', 'pdf_path', 'video_url'])]
class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<RecipeCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(RecipeCategory::class, 'recipe_category_id');
    }

    /**
     * @return BelongsToMany<SchoolClass, $this>
     */
    public function schoolClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class)->orderBy('position');
    }

    /**
     * @return HasMany<RecipeIngredient, $this>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<RecipePhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(RecipePhoto::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Commitment, $this>
     */
    public function commitments(): HasMany
    {
        return $this->hasMany(Commitment::class);
    }

    /**
     * Uploaded PDF first, external link otherwise.
     */
    public function pdfLink(): ?string
    {
        return $this->pdf_path
            ? Storage::disk('public')->url($this->pdf_path)
            : $this->pdf_url;
    }

    /**
     * Embeddable player address, or null when the link is not a YouTube video.
     */
    public function videoEmbedUrl(): ?string
    {
        return YouTube::embedUrl($this->video_url);
    }

    /**
     * Orderable ingredients of this recipe: key => grams to order per kilo produced.
     *
     * @return array<string, int>
     */
    public function orderableGramsPerKg(): array
    {
        return $this->ingredients
            ->filter(fn (RecipeIngredient $ingredient) => $ingredient->isOrderable())
            ->mapWithKeys(fn (RecipeIngredient $ingredient) => [$ingredient->orderable_key => (int) $ingredient->grams_per_kg])
            ->all();
    }
}
