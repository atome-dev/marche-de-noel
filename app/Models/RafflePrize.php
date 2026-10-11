<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RafflePrizeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A raffle prize. Its number is its rank in the list until the list is
 * validated, then it is frozen.
 *
 * @property int $id
 * @property string $label
 * @property int $position
 * @property int|null $number
 * @property string|null $winning_ticket
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['label', 'position', 'number', 'winning_ticket'])]
class RafflePrize extends Model
{
    /** @use HasFactory<RafflePrizeFactory> */
    use HasFactory;

    /**
     * Number shown to everyone: frozen once the list is validated.
     */
    public function displayNumber(): int
    {
        return $this->number ?? $this->position;
    }

    /**
     * @param  Builder<RafflePrize>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }
}
