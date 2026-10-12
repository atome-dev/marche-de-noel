<?php

namespace App\Auth;

use App\Mail\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Accounts created by an administrator for someone else, typically a stand
 * manager. The account cannot be used until the person follows the emailed
 * link, which verifies the address and lets them choose their password.
 */
class Invitations
{
    /**
     * @param  array<int, int>  $standIds  stands the person will manage
     */
    public function invite(string $firstName, string $lastName, string $email, array $standIds = [], ?string $message = null): User
    {
        $user = DB::transaction(function () use ($firstName, $lastName, $email, $standIds): User {
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => Str::lower($email),
                'locale' => config('app.locale'),
                // Unguessable: nobody can sign in before the invitation is accepted.
                'password' => Str::password(40),
            ]);

            $user->managedStands()->sync($standIds);

            return $user;
        });

        $this->send($user, $message);

        return $user;
    }

    public function send(User $user, ?string $message = null): void
    {
        Mail::to($user->email)->send(new Invitation($user, $this->url($user), filled($message) ? trim($message) : null));
    }

    public function url(User $user): string
    {
        return URL::temporarySignedRoute(
            'invitation.accept',
            now()->addMinutes(config('marche.invitation_link_ttl')),
            ['user' => $user->id],
        );
    }

    /**
     * An invitation is pending until the address is verified.
     */
    public function isPending(User $user): bool
    {
        return $user->email_verified_at === null;
    }
}
