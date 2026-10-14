<?php

use App\Models\User;

test('the home page redirects guests to the login page', function () {
    $this->get('/')->assertRedirect('/dashboard');
    $this->get('/dashboard')->assertRedirect(route('login'));
});

test('pages are never indexed', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', escape: false);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', escape: false);
});

test('robots.txt disallows every crawler', function () {
    expect(file_get_contents(public_path('robots.txt')))->toContain('Disallow: /');
});

test('the dashboard shows the market name and dates', function () {
    config([
        'marche.name' => 'Marché de Noël 2026',
        'marche.dates' => ['2026-11-28', '2026-11-29'],
    ]);

    $this->actingAs(User::factory()->create(['first_name' => 'Camille']))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Marché de Noël 2026')
        ->assertSee('Camille')
        ->assertSee('28 novembre');
});

test('the admin menu is only shown to administrators', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertDontSee(route('admin.users'));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertSee(route('admin.users'));
});

test('confirmations open the site dialog instead of the browser one', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-confirm-dialog', escape: false)
        ->assertSee(__('actions.confirm_title'))
        ->assertSee('data-confirm-prompt', escape: false);
});
