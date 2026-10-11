<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('an existing user is promoted to administrator', function () {
    $user = User::factory()->create(['email' => 'camille@example.com']);

    $this->artisan('marche:make-admin', ['email' => 'Camille@Example.com'])
        ->assertSuccessful();

    expect($user->refresh()->role)->toBe(Role::Admin);
});

test('a missing user is created as a verified administrator', function () {
    $this->artisan('marche:make-admin', ['email' => 'admin@example.com'])
        ->expectsQuestion(__('commands.make_admin.first_name'), 'Alex')
        ->expectsQuestion(__('commands.make_admin.last_name'), 'Durand')
        ->expectsQuestion(__('commands.make_admin.password'), 'Un-mot-de-passe-1')
        ->assertSuccessful();

    $admin = User::firstWhere('email', 'admin@example.com');

    expect($admin->role)->toBe(Role::Admin)
        ->and($admin->name)->toBe('Alex Durand')
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and(Hash::check('Un-mot-de-passe-1', $admin->password))->toBeTrue();
});

test('an invalid email is rejected', function () {
    $this->artisan('marche:make-admin', ['email' => 'not-an-email'])
        ->assertFailed();

    expect(User::count())->toBe(0);
});
