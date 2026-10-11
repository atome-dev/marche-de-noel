<?php

use App\Livewire\Settings\Security;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('password settings page can be rendered', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee(__('settings.password.heading'));
});

test('password can be updated', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    Livewire::test(Security::class)
        ->set('current_password', 'password')
        ->set('password', 'nouveau1')
        ->set('password_confirmation', 'nouveau1')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('nouveau1', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    Livewire::test(Security::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'nouveau1')
        ->set('password_confirmation', 'nouveau1')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);
});
