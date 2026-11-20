<?php

namespace App\Planning;

use App\Exceptions\RegistrationRefused;
use App\Models\Registration;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Registration rules for the volunteer planning.
 */
class SlotRegistrar
{
    /**
     * Why the user cannot register on this slot, or null when allowed.
     */
    public function refusalFor(User $user, Slot $slot): ?string
    {
        $slot->loadMissing(['stand.schoolClasses', 'registrations', 'reservedClasses']);
        $user->loadMissing('schoolClasses');

        return match (true) {
            ! $slot->stand->is_active => 'inactive',
            $slot->hasVolunteer($user) => 'already_registered',
            $slot->isFull() => 'full',
            ! $slot->isAccessibleTo($user) => 'reserved',
            ! $slot->isReservedFor($user) && ! $slot->stand->acceptsVolunteer($user) => 'other_classes',
            default => null,
        };
    }

    /**
     * @throws RegistrationRefused
     */
    public function register(User $user, Slot $slot, ?string $message = null): Registration
    {
        return DB::transaction(function () use ($user, $slot, $message): Registration {
            $slot = $this->lock($slot);

            if ($reason = $this->refusalFor($user, $slot)) {
                throw new RegistrationRefused($reason);
            }

            return $slot->registrations()->create([
                'user_id' => $user->id,
                'message' => filled($message) ? $message : null,
            ]);
        });
    }

    public function unregister(User $user, Slot $slot): void
    {
        $slot->registrations()->where('user_id', $user->id)->delete();
    }

    /**
     * Why the user cannot add a person without account on this slot, or null
     * when allowed. Managers only need a free place; other volunteers may add
     * someone wherever they could register themselves.
     */
    public function guestRefusalFor(User $user, Slot $slot, bool $isManager = false): ?string
    {
        $slot->loadMissing(['stand', 'registrations']);

        if (! $slot->stand->is_active) {
            return 'inactive';
        }

        if ($slot->isFull()) {
            return 'full';
        }

        if ($isManager) {
            return null;
        }

        $refusal = $this->refusalFor($user, $slot);

        return $refusal === 'already_registered' ? null : $refusal;
    }

    /**
     * Fills a place with a person who has no account.
     *
     * @throws RegistrationRefused
     */
    public function addGuest(Slot $slot, string $name, ?User $addedBy = null, bool $isManager = true): Registration
    {
        return DB::transaction(function () use ($slot, $name, $addedBy, $isManager): Registration {
            $slot = $this->lock($slot);

            $refusal = $addedBy === null
                ? ($slot->isFull() ? 'full' : null)
                : $this->guestRefusalFor($addedBy, $slot, $isManager);

            if ($refusal !== null) {
                throw new RegistrationRefused($refusal);
            }

            return $slot->registrations()->create([
                'guest_name' => $name,
                'added_by_id' => $addedBy?->id,
            ]);
        });
    }

    /**
     * Slots of the user that overlap this one (only a warning).
     *
     * @return Collection<int, Slot>
     */
    public function overlapsFor(User $user, Slot $slot): Collection
    {
        return Slot::query()
            ->with('stand')
            ->whereKeyNot($slot->id)
            ->whereDate('date', $slot->date)
            ->where('starts_at', '<', $slot->ends_at)
            ->where('ends_at', '>', $slot->starts_at)
            ->whereHas('registrations', fn ($query) => $query->where('user_id', $user->id))
            ->orderBy('starts_at')
            ->get();
    }

    private function lock(Slot $slot): Slot
    {
        return Slot::query()->lockForUpdate()->findOrFail($slot->id);
    }
}
