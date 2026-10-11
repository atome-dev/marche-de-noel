<?php

namespace App\Livewire\Auth;

use App\Concerns\ProfileValidationRules;
use App\Mail\RegistrationLink;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * First registration step: the shared password unlocks an email
 * containing a signed link to create the account.
 */
#[Layout('layouts::auth')]
class Register extends Component
{
    use ProfileValidationRules;

    private const MAX_ATTEMPTS = 5;

    public string $email = '';

    public string $temporaryPassword = '';

    public bool $linkSent = false;

    public function submit(): void
    {
        $this->email = Str::lower(trim($this->email));

        $this->ensureIsNotRateLimited();

        $this->validate([
            'email' => $this->emailRules(),
            'temporaryPassword' => ['required', 'string'],
        ], [
            'email.unique' => __('registration.request.already_registered'),
        ]);

        if (! $this->temporaryPasswordMatches()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'temporaryPassword' => __('registration.request.wrong_password'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $url = URL::temporarySignedRoute(
            'register.complete',
            now()->addMinutes(config('marche.registration_link_ttl')),
            ['email' => $this->email],
        );

        Mail::to($this->email)->send(new RegistrationLink($url));

        $this->reset('temporaryPassword');
        $this->linkSent = true;
    }

    public function render(): View
    {
        return view('livewire.auth.register')
            ->title(__('registration.request.title'));
    }

    private function temporaryPasswordMatches(): bool
    {
        $expected = (string) config('marche.temp_password');

        return $expected !== '' && hash_equals($expected, $this->temporaryPassword);
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds = RateLimiter::availableIn($this->throttleKey()),
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(): string
    {
        return 'register:'.request()->ip();
    }
}
