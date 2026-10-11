<?php

namespace App\Livewire\Auth;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Second registration step, reached through the signed link sent by email:
 * the address is therefore verified.
 */
#[Layout('layouts::auth')]
class CompleteRegistration extends Component
{
    use PasswordValidationRules, ProfileValidationRules;

    #[Locked]
    public string $email = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $phone = '';

    public string $locale = '';

    /** @var array<int, int|string> */
    public array $schoolClassIds = [];

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->email = Str::lower((string) request()->query('email'));
        $this->locale = config('app.locale');

        if (User::where('email', $this->email)->exists()) {
            session()->flash('status', __('registration.complete.already_registered'));

            $this->redirectRoute('login');
        }
    }

    public function register(): void
    {
        $validated = $this->validate([
            ...$this->profileRules(),
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = new User([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?: null,
                'locale' => $validated['locale'],
                'password' => $validated['password'],
            ]);
            $user->email_verified_at = now();
            $user->save();

            $user->schoolClasses()->sync($validated['schoolClassIds'] ?? []);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);
        session()->regenerate();

        $this->redirectRoute('dashboard', navigate: true);
    }

    /**
     * @return Collection<int, SchoolClass>
     */
    #[Computed]
    public function schoolClasses(): Collection
    {
        return SchoolClass::query()->ordered()->get();
    }

    public function render(): View
    {
        return view('livewire.auth.complete-registration')
            ->title(__('registration.complete.title'));
    }
}
