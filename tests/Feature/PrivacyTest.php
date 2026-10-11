<?php

use App\Models\User;

test('guests can read the privacy page', function () {
    config(['marche.privacy.contact_email' => 'contact@example.org', 'marche.name' => 'Marché de Noël 2026']);

    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('Protection des données personnelles')
        ->assertSee('Marché de Noël 2026')
        ->assertSee('contact@example.org')
        ->assertDontSee(':email')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', escape: false);
});

test('the login and registration pages link to the privacy page', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee(route('privacy'));
})->with(['login', 'register']);

test('the menu links to the privacy page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('privacy'));
});
