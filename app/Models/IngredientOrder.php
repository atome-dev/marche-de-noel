<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\IngredientOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An orderable ingredient ordered for a commitment.
 *
 * @property int $id
 * @property int $commitment_id
 * @property string $orderable_key
 * @property int $grams
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Commitment $commitment
 */
#[Fillable(['orderable_key', 'grams'])]
class IngredientOrder extends Model
{
    /** @use HasFactory<IngredientOrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grams' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Commitment, $this>
     */
    public function commitment(): BelongsTo
    {
        return $this->belongsTo(Commitment::class);
    }
}
