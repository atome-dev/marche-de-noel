<?php

use App\Enums\Role;
use App\Livewire\Auth\CompleteRegistration;
use App\Livewire\Auth\Register;
use App\Mail\RegistrationLink;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    config(['marche.temp_password' => 'Exemple-Commun1']);
    Mail::fake();
    RateLimiter::clear('register:127.0.0.1');
});

function registrationUrl(string $email, int $minutes = 60): string
{
    return URL::temporarySignedRoute('register.complete', now()->addMinutes($minutes), ['email' => $email]);
}

describe('request access', function () {
    test('registration screen can be rendered', function () {
        $this->get(route('register'))
            ->assertOk()
            ->assertSeeLivewire(Register::class);
    });

    test('a registration link is emailed when the shared password is correct', function () {
        Livewire::test(Register::class)
            ->set('email', 'Camille@Example.com')
            ->set('temporaryPassword', 'Exemple-Commun1')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('linkSent', true);

        Mail::assertSent(RegistrationLink::class, function (RegistrationLink $mail) {
            return $mail->hasTo('camille@example.com')
                && str_contains($mail->url, 'signature=')
                && str_contains($mail->url, 'email=camille%40example.com');
        });

        expect(User::count())->toBe(0);
    });

    test('a wrong shared password is rejected', function () {
        Livewire::test(Register::class)
            ->set('email', 'camille@example.com')
            ->set('temporaryPassword', 'wrong')
            ->call('submit')
            ->assertHasErrors(['temporaryPassword']);

        Mail::assertNothingSent();
    });

    test('registration is closed when no shared password is configured', function () {
        config(['marche.temp_password' => null]);

        Livewire::test(Register::class)
            ->set('email', 'camille@example.com')
            ->set('temporaryPassword', 'anything')
            ->call('submit')
            ->assertHasErrors(['temporaryPassword']);

        Mail::assertNothingSent();
    });

    test('an already registered email is rejected', function () {
        User::factory()->create(['email' => 'camille@example.com']);

        Livewire::test(Register::class)
            ->set('email', 'camille@example.com')
            ->set('temporaryPassword', 'Exemple-Commun1')
            ->call('submit')
            ->assertHasErrors(['email']);

        Mail::assertNothingSent();
    });

    test('attempts are rate limited', function () {
        $component = Livewire::test(Register::class)->set('email', 'camille@example.com');

        foreach (range(1, 5) as $attempt) {
            $component->set('temporaryPassword', 'wrong')->call('submit');
        }

        $component->set('temporaryPassword', 'Exemple-Commun1')
            ->call('submit')
            ->assertHasErrors(['email']);

        Mail::assertNothingSent();
    });
});

describe('complete registration', function () {
    test('the completion screen requires a valid signature', function () {
        $this->get(route('register.complete', ['email' => 'camille@example.com']))
            ->assertForbidden();
    });

    test('the completion screen rejects an expired link', function () {
        $url = registrationUrl('camille@example.com', minutes: 10);

        $this->travel(11)->minutes();

        $this->get($url)->assertForbidden();
    });

    test('the completion screen can be rendered with a signed link', function () {
        $this->get(registrationUrl('camille@example.com'))
            ->assertOk()
            ->assertSeeLivewire(CompleteRegistration::class)
            ->assertSee('camille@example.com');
    });

    test('a completed registration creates a verified volunteer and logs in', function () {
        $classes = SchoolClass::query()->ordered()->take(2)->pluck('id')->all();

        Livewire::withQueryParams(['email' => 'camille@example.com'])
            ->test(CompleteRegistration::class)
            ->set('first_name', 'Camille')
            ->set('last_name', 'Martin')
            ->set('phone', '06 12 34 56 78')
            ->set('locale', 'fr')
            ->set('schoolClassIds', $classes)
            ->set('password', 'Un-mot-de-passe-1')
            ->set('password_confirmation', 'Un-mot-de-passe-1')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::firstWhere('email', 'camille@example.com');

        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('Camille Martin')
            ->and($user->role)->toBe(Role::Volunteer)
            ->and($user->email_verified_at)->not->toBeNull()
            ->and($user->schoolClasses->pluck('id')->all())->toEqualCanonicalizing($classes);

        $this->assertAuthenticatedAs($user);
    });

    test('phone and classes are optional', function () {
        Livewire::withQueryParams(['email' => 'prof@example.com'])
            ->test(CompleteRegistration::class)
            ->set('first_name', 'Alex')
            ->set('last_name', 'Durand')
            ->set('password', 'secret1')
            ->set('password_confirmation', 'secret1')
            ->call('register')
            ->assertHasNoErrors();

        $user = User::firstWhere('email', 'prof@example.com');

        expect($user->phone)->toBeNull()
            ->and($user->schoolClasses)->toBeEmpty();
    });

    test('passwords need at least 6 characters with letters and numbers', function (string $password, bool $valid) {
        $component = Livewire::withQueryParams(['email' => 'camille@example.com'])
            ->test(CompleteRegistration::class)
            ->set('first_name', 'Camille')
            ->set('last_name', 'Martin')
            ->set('password', $password)
            ->set('password_confirmation', $password)
            ->call('register');

        $valid ? $component->assertHasNoErrors() : $component->assertHasErrors(['password']);
    })->with([
        'too short' => ['abc12', false],
        'no number' => ['abcdef', false],
        'no letter' => ['123456', false],
        'valid' => ['abcde1', true],
    ]);

    test('profile fields and password are validated', function () {
        Livewire::withQueryParams(['email' => 'camille@example.com'])
            ->test(CompleteRegistration::class)
            ->set('phone', 'abc')
            ->set('locale', 'xx')
            ->set('schoolClassIds', [999999])
            ->set('password', 'abc')
            ->set('password_confirmation', 'abd')
            ->call('register')
            ->assertHasErrors(['first_name', 'last_name', 'phone', 'locale', 'schoolClassIds.0', 'password']);

        expect(User::count())->toBe(0);
    });

    test('the email cannot be tampered with after mount', function () {
        Livewire::withQueryParams(['email' => 'camille@example.com'])
            ->test(CompleteRegistration::class)
            ->set('email', 'other@example.com');
    })->throws(Exception::class);

    test('a link for an already registered email redirects to login', function () {
        User::factory()->create(['email' => 'camille@example.com']);

        $this->get(registrationUrl('camille@example.com'))
            ->assertRedirect(route('login'));
    });
});
