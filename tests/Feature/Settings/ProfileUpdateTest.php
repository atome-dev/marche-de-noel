<?php

use App\Livewire\Settings\Profile;
use App\Mail\EmailChangeLink;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
});

function emailChangeUrl(User $user, string $email): string
{
    return URL::temporarySignedRoute('profile.email.confirm', now()->addHour(), ['user' => $user->id, 'email' => $email]);
}

test('profile page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('profile.edit'))
        ->assertOk()
        ->assertSeeLivewire(Profile::class);
});

test('profile is prefilled with the current values', function () {
    $user = User::factory()->create(['first_name' => 'Camille', 'phone' => '06 11 22 33 44']);
    $class = SchoolClass::query()->ordered()->first();
    $user->schoolClasses()->attach($class);

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->assertSet('first_name', 'Camille')
        ->assertSet('phone', '06 11 22 33 44')
        ->assertSet('schoolClassIds', [$class->id]);
});

test('profile information can be updated', function () {
    $user = User::factory()->create(['email' => 'camille@example.com']);
    $classes = SchoolClass::query()->ordered()->skip(3)->take(2)->pluck('id')->all();

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('first_name', 'Camille')
        ->set('last_name', 'Martin')
        ->set('phone', '+33 6 12 34 56 78')
        ->set('locale', 'fr')
        ->set('schoolClassIds', $classes)
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Camille Martin')
        ->and($user->phone)->toBe('+33 6 12 34 56 78')
        ->and($user->schoolClasses->pluck('id')->all())->toEqualCanonicalizing($classes);
    Mail::assertNothingSent();
});

test('the phone number can be removed', function () {
    $user = User::factory()->create(['phone' => '06 11 22 33 44']);

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('phone', '')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->refresh()->phone)->toBeNull();
});

describe('email change', function () {
    test('a new email is only applied after confirmation', function () {
        $user = User::factory()->create(['email' => 'camille@example.com']);

        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->set('email', 'Nouvelle@Example.com')
            ->call('updateProfileInformation')
            ->assertHasNoErrors()
            ->assertSet('email', 'camille@example.com');

        expect($user->refresh()->email)->toBe('camille@example.com');

        Mail::assertSent(EmailChangeLink::class, fn (EmailChangeLink $mail) => $mail->hasTo('nouvelle@example.com')
            && str_contains($mail->url, 'signature='));
    });

    test('the confirmation link changes the email', function () {
        $user = User::factory()->create(['email' => 'camille@example.com']);

        $this->actingAs($user)
            ->get(emailChangeUrl($user, 'nouvelle@example.com'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status');

        expect($user->refresh()->email)->toBe('nouvelle@example.com');
    });

    test('the confirmation link also works when logged out', function () {
        $user = User::factory()->create(['email' => 'camille@example.com']);

        $this->get(emailChangeUrl($user, 'nouvelle@example.com'))
            ->assertRedirect(route('login'));

        expect($user->refresh()->email)->toBe('nouvelle@example.com');
    });

    test('the confirmation link requires a valid signature', function () {
        $user = User::factory()->create(['email' => 'camille@example.com']);

        $this->get(route('profile.email.confirm', ['user' => $user->id, 'email' => 'pirate@example.com']))
            ->assertForbidden();

        expect($user->refresh()->email)->toBe('camille@example.com');
    });

    test('the confirmation fails when the email was taken in the meantime', function () {
        $user = User::factory()->create(['email' => 'camille@example.com']);
        $url = emailChangeUrl($user, 'nouvelle@example.com');
        User::factory()->create(['email' => 'nouvelle@example.com']);

        $this->actingAs($user)->get($url)->assertRedirect(route('profile.edit'));

        expect($user->refresh()->email)->toBe('camille@example.com');
    });

    test('the new email must not be used by another account', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs(User::factory()->create());

        Livewire::test(Profile::class)
            ->set('email', 'taken@example.com')
            ->call('updateProfileInformation')
            ->assertHasErrors(['email' => 'unique']);

        Mail::assertNothingSent();
    });
});

test('profile fields are validated', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Profile::class)
        ->set('first_name', '')
        ->set('phone', 'abc')
        ->set('locale', 'xx')
        ->call('updateProfileInformation')
        ->assertHasErrors(['first_name', 'phone', 'locale']);
});

test('user can delete their account', function () {
    $user = User::factory()->create();
    $user->schoolClasses()->attach(SchoolClass::query()->first());

    $this->actingAs($user);

    Livewire::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull()
        ->and(auth()->check())->toBeFalse();
    $this->assertDatabaseMissing('school_class_user', ['user_id' => $user->id]);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('settings.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});
