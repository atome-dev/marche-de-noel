<?php

use App\Livewire\Concert\Show;
use App\Models\User;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();
    config([
        'marche.timezone' => 'Europe/Paris',
        'trees.helloasso' => ['client_id' => 'id', 'client_secret' => 'secret', 'organization_slug' => 'ecole', 'sandbox' => true],
        'concert' => ['form_slug' => 'orchestre', 'url' => 'https://www.helloasso.com/associations/ecole/evenements/orchestre', 'display' => 'iframe', 'cache_seconds' => 60],
    ]);
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/forms/Event/orchestre/public' => Http::response(['title' => 'Orchestre des élèves', 'startDate' => '2026-11-27T20:00:00+01:00', 'place' => ['name' => 'Salle Odilon'], 'tiers' => [['label' => 'Adulte', 'price' => 1000]]]),
        '*/forms/Event/orchestre/items*' => Http::response(['data' => [
            helloAssoTicket(['id' => 1, 'user' => ['firstName' => 'Léo', 'lastName' => 'Martin'], 'payer' => ['email' => 'Camille@example.org'], 'isScanned' => true]),
            helloAssoTicket(['id' => 2, 'user' => ['firstName' => 'Sacha', 'lastName' => 'Autre'], 'payer' => ['email' => 'autre@example.org'], 'ticketUrl' => 'https://ticket/autre']),
        ], 'pagination' => ['totalPages' => 1]]),
    ]);

    $this->user = User::factory()->create(['email' => 'camille@example.org']);
    $this->actingAs($this->user);
});

test('guests are sent to the login page', function () {
    auth()->logout();

    $this->get(route('concert.index'))->assertRedirect(route('login'));
});

test('the page shows the event and the ticketing widget', function () {
    $this->get(route('concert.index'))
        ->assertOk()
        ->assertSee('Orchestre des élèves')
        ->assertSee('Salle Odilon')
        ->assertSee("10\u{00A0}€", false)
        ->assertSee('src="https://www.helloasso.com/associations/ecole/evenements/orchestre/widget"', false);
});

test('the ticketing opens with a link in link mode', function () {
    config(['concert.display' => 'link']);

    Livewire::test(Show::class)
        ->assertSee(__('concert.book'))
        ->assertDontSee('/widget');
});

test('a family sees its own tickets only', function () {
    Livewire::test(Show::class)
        ->assertSee('Léo Martin')
        ->assertSee(__('concert.mine.scanned'))
        ->assertSee('https://www.helloasso-sandbox.com/ticket?ticketId=110885')
        ->assertDontSee('Sacha Autre')
        ->assertDontSee('https://ticket/autre');
});

test('a family without tickets is told to use the same address', function () {
    $this->actingAs(User::factory()->create(['email' => 'nouveau@example.org']));

    Livewire::test(Show::class)->assertSee(__('concert.mine.empty', ['email' => 'nouveau@example.org']));
});

test('without API keys only the ticketing is shown', function () {
    config(['trees.helloasso.client_id' => null]);

    Livewire::test(Show::class)
        ->assertSee('/widget')
        ->assertDontSee(__('concert.mine.heading'));

    Http::assertNothingSent();
});

test('HelloAsso being down keeps the ticketing and shows a message', function () {
    Http::swap(new Factory);
    Http::fake(['*' => Http::response([], 503)]);

    Livewire::test(Show::class)
        ->assertSee(__('concert.unavailable'))
        ->assertSee('/widget');
});
