<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Applies a new email address once the signed link sent to it is opened.
 */
class ConfirmEmailChange extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        $email = Str::lower((string) $request->query('email'));
        $redirect = $request->user() ? to_route('profile.edit') : to_route('login');

        if (User::where('email', $email)->whereKeyNot($user->id)->exists()) {
            return $redirect->with('status', __('settings.email_change.taken'));
        }

        $user->email = $email;
        $user->email_verified_at = now();
        $user->save();

        return $redirect->with('status', __('settings.email_change.confirmed'));
    }
}
