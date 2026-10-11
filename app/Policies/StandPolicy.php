<?php

namespace App\Policies;

use App\Models\Stand;
use App\Models\User;

/**
 * Administrators are granted everything by Gate::before.
 */
class StandPolicy
{
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Managers freely edit their stand, its slots and its registrations.
     */
    public function update(User $user, Stand $stand): bool
    {
        return $stand->isManagedBy($user);
    }

    public function delete(User $user, Stand $stand): bool
    {
        return false;
    }
}
