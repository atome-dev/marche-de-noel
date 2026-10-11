<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A yearly document uploaded by an administrator (newspaper, order forms…).
 *
 * @property int $id
 * @property string $title
 * @property string $path
 * @property int $size
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'path', 'size', 'position'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    public const DISK = 'local';

    public const DIRECTORY = 'documents';

    public function extension(): string
    {
        return Str::lower(pathinfo($this->path, PATHINFO_EXTENSION));
    }

    /**
     * File name offered to the browser, built from the title.
     */
    public function downloadName(): string
    {
        return (Str::slug($this->title) ?: 'document').'.'.$this->extension();
    }

    public function sizeLabel(): string
    {
        if ($this->size < 1024 * 1024) {
            return max(1, (int) round($this->size / 1024)).' Ko';
        }

        return number_format($this->size / (1024 * 1024), 1, ',', ' ').' Mo';
    }

    /**
     * @param  Builder<Document>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }
}
