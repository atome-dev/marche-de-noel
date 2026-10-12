<?php

use App\Auth\Impersonation;
use App\Livewire\Admin\Users;
use App\Models\Stand;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['first_name' => 'Alex', 'last_name' => 'Durand']);
    $this->volunteer = User::factory()->create(['first_name' => 'Camille', 'last_name' => 'Martin']);
});

function actAs(User $admin, User $target): void
{
    test()->actingAs($admin);

    Livewire::test(Users::class)->call('impersonate', $target->id)->assertRedirect(route('dashboard'));
}

test('an administrator acts as a volunteer and sees the banner', function () {
    actAs($this->admin, $this->volunteer);

    $this->assertAuthenticatedAs($this->volunteer);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('admin.impersonation.banner', ['name' => 'Camille Martin']))
        ->assertSee(route('impersonation.stop'))
        // The volunteer's rights, not the administrator's.
        ->assertDontSee(route('admin.users'));

    $this->get(route('admin.users'))->assertForbidden();
});

test('the manager view of a stand can be checked', function () {
    $stand = Stand::factory()->create();
    $stand->managers()->attach($this->volunteer);
    $other = Stand::factory()->create();

    actAs($this->admin, $this->volunteer);

    $this->get(route('stands.edit', $stand))->assertOk();
    $this->get(route('stands.edit', $other))->assertForbidden();
});

test('coming back restores the administrator account', function () {
    actAs($this->admin, $this->volunteer);

    $this->post(route('impersonation.stop'))->assertRedirect(route('admin.users'));

    $this->assertAuthenticatedAs($this->admin);

    $this->get(route('dashboard'))->assertOk()->assertDontSee('data-impersonation-banner', false);
    $this->get(route('admin.users'))->assertOk();
});

test('no banner is shown outside of an impersonation', function () {
    $this->actingAs($this->volunteer)
        ->get(route('dashboard'))
        ->assertDontSee('data-impersonation-banner', false);
});

test('the account pages are forbidden while acting as someone', function () {
    actAs($this->admin, $this->volunteer);

    $this->get(route('profile.edit'))->assertForbidden();
    $this->get(route('security.edit'))->assertForbidden();
});

test('only administrators can act as someone, and never as an administrator or themselves', function () {
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($this->volunteer);
    Livewire::test(Users::class)->assertForbidden();
    expect(app(Impersonation::class)->canStart($this->volunteer, $this->admin))->toBeFalse();

    $this->actingAs($this->admin);
    Livewire::test(Users::class)->call('impersonate', $otherAdmin->id)->assertForbidden();
    Livewire::test(Users::class)->call('impersonate', $this->admin->id)->assertForbidden();

    $this->assertAuthenticatedAs($this->admin);
    expect(app(Impersonation::class)->isActive())->toBeFalse();
});

test('a volunteer cannot become an administrator by forging the stop request', function () {
    $this->actingAs($this->volunteer)
        ->post(route('impersonation.stop'))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->volunteer);

    // A session pointing to a non-administrator is ignored too.
    $this->withSession([Impersonation::SESSION_KEY => User::factory()->create()->id])
        ->post(route('impersonation.stop'))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->volunteer);
});

test('the act as button is not offered for administrators', function () {
    User::query()->whereKeyNot($this->admin->id)->delete();

    $this->actingAs($this->admin);

    Livewire::test(Users::class)->assertDontSee(__('admin.impersonation.start'));

    User::factory()->create();

    Livewire::test(Users::class)->assertSee(__('admin.impersonation.start'));
});
