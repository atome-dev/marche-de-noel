<?php

namespace App\Models;

use App\Enums\StandType;
use Carbon\CarbonImmutable;
use Database\Factories\StandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property StandType $type
 * @property bool $is_active
 * @property bool $is_open_to_all
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, User> $managers
 * @property-read Collection<int, SchoolClass> $schoolClasses
 * @property-read Collection<int, Slot> $slots
 */
#[Fillable(['name', 'description', 'type', 'is_active', 'is_open_to_all', 'position'])]
class Stand extends Model
{
    /** @use HasFactory<StandFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StandType::class,
            'is_active' => 'boolean',
            'is_open_to_all' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'stand_manager')
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    /**
     * @return BelongsToMany<SchoolClass, $this>
     */
    public function schoolClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class)->orderBy('position');
    }

    /**
     * @return HasMany<Slot, $this>
     */
    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class)
            ->orderBy('date')
            ->orderBy('starts_at')
            ->orderBy('ends_at');
    }

    public function isManagedBy(User $user): bool
    {
        if ($this->relationLoaded('managers')) {
            return $this->managers->contains($user);
        }

        return $this->managers()->whereKey($user->id)->exists();
    }

    /**
     * Stands linked to classes only accept volunteers from these classes,
     * unless a manager opened the stand to everyone.
     */
    public function acceptsVolunteer(User $user): bool
    {
        if ($this->is_open_to_all || $this->schoolClasses->isEmpty()) {
            return true;
        }

        return $this->schoolClasses->pluck('id')
            ->intersect($user->schoolClasses->pluck('id'))
            ->isNotEmpty();
    }

    /**
     * @param  Builder<Stand>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('name');
    }

    /**
     * Active stands that need at least one volunteer.
     *
     * @param  Builder<Stand>  $query
     */
    public function scopeInPlanning(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereHas('slots', fn (Builder $slots) => $slots->where('capacity', '>', 0));
    }
}
