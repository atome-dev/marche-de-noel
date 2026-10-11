<?php

use App\Models\User;

test('the user locale is applied when it is enabled', function () {
    config(['marche.locales' => ['fr', 'en']]);

    $this->actingAs(User::factory()->create(['locale' => 'en']))
        ->get(route('dashboard'))
        ->assertOk();

    expect(app()->getLocale())->toBe('en');
});

test('a disabled user locale falls back to the default locale', function () {
    config(['marche.locales' => ['fr']]);

    $this->actingAs(User::factory()->create(['locale' => 'de']))
        ->get(route('dashboard'))
        ->assertOk();

    expect(app()->getLocale())->toBe('fr');
});
