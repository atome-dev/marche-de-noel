<?php

namespace App\Livewire\Auth;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Reached through the signed link of an invitation: the address is therefore
 * verified. The invited person checks their profile and chooses a password.
 */
#[Layout('layouts::auth')]
class AcceptInvitation extends Component
{
    use PasswordValidationRules, ProfileValidationRules;

    #[Locked]
    public int $userId;

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

    public function mount(User $user): void
    {
        if ($user->email_verified_at !== null) {
            session()->flash('status', __('invitations.accept.already_accepted'));

            $this->redirectRoute('login');

            return;
        }

        $this->userId = $user->id;
        $this->email = $user->email;
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->locale = $user->locale ?: config('app.locale');
    }

    public function accept(): void
    {
        $user = User::findOrFail($this->userId);

        abort_if($user->email_verified_at !== null, 403);

        $validated = $this->validate([
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ]);

        DB::transaction(function () use ($user, $validated): void {
            $user->fill([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?: null,
                'locale' => $validated['locale'],
                'password' => $validated['password'],
            ]);
            $user->email_verified_at = now();
            $user->save();

            $user->schoolClasses()->sync($validated['schoolClassIds'] ?? []);
        });

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
        return view('livewire.auth.accept-invitation')
            ->title(__('invitations.accept.title'));
    }
}
