<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * "Act as": an administrator signs in as another user to check what that
 * person sees and may do, then comes back to their own account.
 */
class Impersonation
{
    public const SESSION_KEY = 'impersonator_id';

    public function canStart(User $admin, User $target): bool
    {
        return $admin->isAdmin()
            && ! $this->isActive()
            && ! $target->is($admin)
            // Administrators see everything already.
            && ! $target->isAdmin();
    }

    public function start(User $admin, User $target): void
    {
        abort_unless($this->canStart($admin, $target), 403);

        Log::info('Impersonation started.', ['admin' => $admin->id, 'user' => $target->id]);

        Auth::login($target);

        session()->put(self::SESSION_KEY, $admin->id);
    }

    /**
     * Sign the administrator back in. Returns false when nobody was impersonated.
     */
    public function stop(): bool
    {
        $admin = $this->impersonator();

        session()->forget(self::SESSION_KEY);

        if ($admin === null) {
            return false;
        }

        Log::info('Impersonation stopped.', ['admin' => $admin->id, 'user' => Auth::id()]);

        Auth::login($admin);

        return true;
    }

    public function isActive(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    /**
     * The administrator behind the current session, still an administrator.
     */
    public function impersonator(): ?User
    {
        $admin = User::query()->whereKey((int) session(self::SESSION_KEY))->first();

        return $admin?->isAdmin() ? $admin : null;
    }
}
