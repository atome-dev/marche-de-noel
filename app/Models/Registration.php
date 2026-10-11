<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A volunteer (or a person without account, added by a manager) on a slot.
 *
 * @property int $id
 * @property int $slot_id
 * @property int|null $user_id
 * @property string|null $guest_name
 * @property string|null $message
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Slot $slot
 * @property-read User|null $user
 */
#[Fillable(['user_id', 'guest_name', 'message'])]
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
