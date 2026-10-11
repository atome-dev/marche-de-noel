<?php

namespace App\Livewire\Settings;

use App\Concerns\ProfileValidationRules;
use App\Mail\EmailChangeLink;
use App\Models\SchoolClass;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Profile extends Component
{
    use ProfileValidationRules;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $locale = '';

    /** @var array<int, int|string> */
    public array $schoolClassIds = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->locale = $user->locale;
        $this->schoolClassIds = $user->schoolClasses->pluck('id')->all();
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate([
            ...$this->profileRules(),
            'email' => $this->emailRules($user->id),
        ]);

        DB::transaction(function () use ($user, $validated): void {
            $user->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?: null,
                'locale' => $validated['locale'],
            ]);

            $user->schoolClasses()->sync($validated['schoolClassIds'] ?? []);
        });

        if ($validated['email'] !== $user->email) {
            $this->sendEmailChangeLink($user, $validated['email']);

            return;
        }

        Flux::toast(variant: 'success', text: __('settings.profile.updated'));
    }

    /**
     * The new address is only applied once confirmed through the emailed link.
     */
    private function sendEmailChangeLink(User $user, string $newEmail): void
    {
        $url = URL::temporarySignedRoute(
            'profile.email.confirm',
            now()->addMinutes(config('marche.registration_link_ttl')),
            ['user' => $user->id, 'email' => $newEmail],
        );

        Mail::to($newEmail)->send(new EmailChangeLink($url));

        $this->email = $user->email;

        Flux::toast(
            variant: 'warning',
            heading: __('settings.profile.updated'),
            text: __('settings.email_change.sent', ['email' => $newEmail]),
            duration: 10000,
        );
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
        return view('livewire.settings.profile')
            ->title(__('settings.profile.title'));
    }
}
