<?php

use App\Enums\Role;
use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

test('roles have a translated label', function () {
    expect(Role::Admin->label())->toBe('Administrateur')
        ->and(Role::Volunteer->label())->toBe('Bénévole');
});

test('only administrators are admins', function () {
    expect((new User)->forceFill(['role' => Role::Admin])->isAdmin())->toBeTrue()
        ->and((new User)->forceFill(['role' => Role::Volunteer])->isAdmin())->toBeFalse();
});

test('initials and full name come from first and last names', function () {
    $user = (new User)->forceFill(['first_name' => 'camille', 'last_name' => 'Martin']);

    expect($user->initials())->toBe('CM')
        ->and($user->name)->toBe('camille Martin');
});
