<?php

use App\Enums\StandType;
use App\Livewire\Concert\Manage;
use App\Models\Stand;
use App\Models\User;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();
    config([
        'trees.helloasso' => ['client_id' => 'id', 'client_secret' => 'secret', 'organization_slug' => 'ecole', 'sandbox' => true],
        'concert' => ['form_slug' => 'orchestre', 'url' => 'https://www.helloasso.com/associations/ecole/evenements/orchestre', 'display' => 'iframe', 'cache_seconds' => 60],
    ]);
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/forms/Event/orchestre/items*' => Http::response(['data' => [
            helloAssoTicket(['id' => 1, 'name' => 'Adulte', 'amount' => 1000, 'user' => ['firstName' => 'Léo', 'lastName' => 'Martin'], 'isScanned' => true]),
            helloAssoTicket(['id' => 2, 'name' => 'Enfant', 'amount' => 500, 'user' => ['firstName' => 'Sacha', 'lastName' => 'Dubois'], 'payer' => ['firstName' => 'Alex', 'lastName' => 'Dubois', 'email' => 'alex@example.org']]),
        ], 'pagination' => ['totalPages' => 1]]),
    ]);

    $this->manager = User::factory()->create();
    Stand::factory()->ofType(StandType::Concert)->create()->managers()->attach($this->manager);
    $this->actingAs($this->manager);
});

test('only concert managers reach the page', function () {
    $this->get(route('concert.manage'))->assertOk();

    $this->actingAs(User::factory()->create())->get(route('concert.manage'))->assertForbidden();
});

test('sales are summed up by tier with the entries', function () {
    Livewire::test(Manage::class)
        ->assertSeeInOrder(['Adulte', "10\u{00A0}€", 'Enfant', "5\u{00A0}€", __('concert.manage.total'), "15\u{00A0}€"], false)
        ->assertSee(__('concert.manage.entries', ['scanned' => 1, 'count' => 2]));
});

test('spectators are listed and searched by name or address', function () {
    Livewire::test(Manage::class)
        ->assertSee('Léo Martin')
        ->assertSee('Sacha Dubois')
        ->set('search', 'ALEX@example')
        ->assertSee('Sacha Dubois')
        ->assertDontSee('Léo Martin')
        ->set('search', 'léo')
        ->assertSee('Léo Martin')
        ->assertDontSee('Sacha Dubois');
});

test('refreshing reads the tickets again', function () {
    Livewire::test(Manage::class)->call('refresh');

    Http::assertSentCount(3); // token, items, items again
});

test('the last known list is shown with a warning when HelloAsso fails', function () {
    Http::swap(new Factory);
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/forms/Event/orchestre/items*' => Http::sequence()
            ->push(['data' => [helloAssoTicket(['user' => ['firstName' => 'Léo', 'lastName' => 'Martin']])], 'pagination' => ['totalPages' => 1]])
            ->push([], 503),
    ]);

    Livewire::test(Manage::class);
    $this->travel(61)->seconds();

    Livewire::test(Manage::class)
        ->assertSee(__('concert.stale'))
        ->assertSee('Léo Martin');
});

test('without API keys the managers are sent to the HelloAsso back office', function () {
    config(['trees.helloasso.client_id' => null]);

    Livewire::test(Manage::class)->assertSee(__('concert.manage.not_connected'));
    Http::assertNothingSent();
});
