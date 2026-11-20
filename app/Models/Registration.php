<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A volunteer (or a person without account, added by any volunteer) on a slot.
 *
 * @property int $id
 * @property int $slot_id
 * @property int|null $user_id
 * @property string|null $guest_name
 * @property int|null $added_by_id
 * @property string|null $message
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Slot $slot
 * @property-read User|null $user
 */
#[Fillable(['user_id', 'guest_name', 'added_by_id', 'message'])]
class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Slot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this person without account was added by the user,
     * who may then remove them.
     */
    public function wasAddedBy(User $user): bool
    {
        return $this->isGuest() && $this->added_by_id === $user->id;
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Short name for everyone, full name for managers and admins.
     */
    public function displayName(bool $full = false): string
    {
        if ($this->user === null) {
            return (string) $this->guest_name;
        }

        return $full ? $this->user->name : $this->user->shortName();
    }
}
