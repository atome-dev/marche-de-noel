<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $stand_id
 * @property CarbonImmutable $date
 * @property string $starts_at
 * @property string $ends_at
 * @property int $capacity
 * @property string|null $label
 * @property bool $is_reserved
 * @property list<string>|null $reserved_emails
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Stand $stand
 * @property-read Collection<int, Registration> $registrations
 * @property-read Collection<int, SchoolClass> $reservedClasses
 */
#[Fillable(['date', 'starts_at', 'ends_at', 'capacity', 'label', 'is_reserved', 'reserved_emails'])]
class Slot extends Model
{
    /** @use HasFactory<SlotFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'capacity' => 'integer',
            'is_reserved' => 'boolean',
            'reserved_emails' => 'array',
        ];
    }

    /**
     * Times are always exposed as HH:MM, whatever the database returns.
     *
     * @return Attribute<string, string>
     */
    protected function startsAt(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): string => substr($value, 0, 5),
            set: fn (string $value): string => substr($value, 0, 5),
        );
    }

    /**
     * @return Attribute<string, string>
     */
    protected function endsAt(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): string => substr($value, 0, 5),
            set: fn (string $value): string => substr($value, 0, 5),
        );
    }

    /**
     * @return BelongsTo<Stand, $this>
     */
    public function stand(): BelongsTo
    {
        return $this->belongsTo(Stand::class);
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class)->oldest('id');
    }

    /**
     * @return BelongsToMany<SchoolClass, $this>
     */
    public function reservedClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class)->orderBy('position');
    }

    /**
     * Key shared by every slot with the same day and times: one planning column.
     */
    public function columnKey(): string
    {
        return $this->date->toDateString().'|'.$this->starts_at.'|'.$this->ends_at;
    }

    public function durationInMinutes(): int
    {
        $toMinutes = fn (string $time): int => (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);

        return $toMinutes($this->ends_at) - $toMinutes($this->starts_at);
    }

    public function takenCount(): int
    {
        return $this->registrations->count();
    }

    public function isFull(): bool
    {
        return $this->takenCount() >= $this->capacity;
    }

    /**
     * Reserved to a list of emails and/or classes, and not opened to all.
     */
    public function isRestricted(): bool
    {
        return $this->is_reserved
            && (filled($this->reserved_emails) || $this->reservedClasses->isNotEmpty());
    }

    public function isReservedFor(User $user): bool
    {
        if (in_array(Str::lower($user->email), $this->reserved_emails ?? [], true)) {
            return true;
        }

        return $this->reservedClasses->pluck('id')
            ->intersect($user->schoolClasses->pluck('id'))
            ->isNotEmpty();
    }

    public function isAccessibleTo(User $user): bool
    {
        return ! $this->isRestricted() || $this->isReservedFor($user);
    }

    /**
     * Planning cell state: reserved (locked for this user), empty, partial or full.
     */
    public function statusFor(User $user, bool $canManage = false): string
    {
        return match (true) {
            ! $canManage && ! $this->isAccessibleTo($user) => 'reserved',
            $this->takenCount() === 0 => 'empty',
            ! $this->isFull() => 'partial',
            default => 'full',
        };
    }

    public function hasVolunteer(User $user): bool
    {
        return $this->registrations->contains('user_id', $user->id);
    }

    public function overlaps(Slot $other): bool
    {
        return $this->date->isSameDay($other->date)
            && $this->starts_at < $other->ends_at
            && $other->starts_at < $this->ends_at;
    }
}
