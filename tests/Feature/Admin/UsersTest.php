<?php

use App\Enums\Role;
use App\Livewire\Admin\Users;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get(route('admin.users'))->assertRedirect(route('login'));
});

test('volunteers cannot access the user administration', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.users'))
        ->assertForbidden();
});

test('admins see every user with their phone number', function () {
    $volunteer = User::factory()->create(['first_name' => 'Camille', 'phone' => '06 98 76 54 32']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.users'))
        ->assertOk()
        ->assertSeeLivewire(Users::class)
        ->assertSee('Camille')
        ->assertSee('06 98 76 54 32');
});

test('admins can search users', function () {
    // Explicit names and emails: random ones sometimes matched the search.
    User::factory()->create(['first_name' => 'Camille', 'last_name' => 'Martin', 'email' => 'camille@example.com']);
    User::factory()->create(['first_name' => 'Dominique', 'last_name' => 'Petit', 'email' => 'dominique@example.com']);

    $this->actingAs(User::factory()->admin()->create(['first_name' => 'Alex', 'last_name' => 'Durand', 'email' => 'alex@example.com']));

    Livewire::test(Users::class)
        ->set('search', 'mart')
        ->assertSee('Camille')
        ->assertDontSee('Dominique');
});

test('admins can change the role of another user', function () {
    $volunteer = User::factory()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Users::class)
        ->call('changeRole', $volunteer->id, Role::Admin->value)
        ->assertHasNoErrors();

    expect($volunteer->refresh()->role)->toBe(Role::Admin);
});

test('admins cannot change their own role', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(Users::class)
        ->call('changeRole', $admin->id, Role::Volunteer->value)
        ->assertForbidden();

    expect($admin->refresh()->role)->toBe(Role::Admin);
});

test('admins can delete another user', function () {
    $volunteer = User::factory()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Users::class)->call('deleteUser', $volunteer->id);

    expect($volunteer->fresh())->toBeNull();
});

test('admins cannot delete themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(Users::class)
        ->call('deleteUser', $admin->id)
        ->assertForbidden();

    expect($admin->fresh())->not->toBeNull();
});

test('volunteers cannot call admin actions', function () {
    $other = User::factory()->create();

    $this->actingAs(User::factory()->create());

    Livewire::test(Users::class)->assertForbidden();

    expect($other->fresh())->not->toBeNull();
});
