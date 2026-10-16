<?php

use App\Concert\ConcertTickets;
use App\Exceptions\HelloAssoUnavailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    config([
        'trees.helloasso' => ['client_id' => 'id', 'client_secret' => 'secret', 'organization_slug' => 'ecole', 'sandbox' => true],
        'concert' => ['form_slug' => 'orchestre', 'url' => 'https://www.helloasso.com/associations/ecole/evenements/orchestre', 'display' => 'iframe', 'cache_seconds' => 60],
    ]);
    $this->travelTo('2026-11-27 18:00:00');
});

function fakeTicketing(array $items, int $status = 200): void
{
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/forms/Event/orchestre/items*' => Http::response(['data' => $items, 'pagination' => ['totalPages' => 1]], $status),
        '*/forms/Event/orchestre/public' => Http::response([
            'title' => 'Orchestre des élèves', 'startDate' => '2026-11-27T20:00:00+01:00',
            'place' => ['name' => 'Salle Odilon', 'address' => '', 'city' => ''],
            'tiers' => [['label' => 'Adulte', 'price' => 1000], ['label' => 'Enfant', 'price' => 500]],
        ], $status),
    ]);
}

test('only valid tickets are listed', function () {
    fakeTicketing([helloAssoTicket(['id' => 1]), helloAssoTicket(['id' => 2, 'state' => 'Canceled'])]);

    $list = app(ConcertTickets::class)->tickets();

    expect($list->tickets)->toHaveCount(1)->and($list->isStale)->toBeFalse();
});

test('tickets are read again only after the cache delay or a forget', function () {
    fakeTicketing([helloAssoTicket()]);
    $tickets = app(ConcertTickets::class);

    $tickets->tickets();
    $tickets->tickets();
    Http::assertSentCount(2); // token + items

    $this->travel(61)->seconds();
    $tickets->tickets();
    Http::assertSentCount(3);

    $tickets->forget();
    $tickets->tickets();
    Http::assertSentCount(4);
});

test('the last known list is served, marked stale, when HelloAsso fails', function () {
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/forms/Event/orchestre/items*' => Http::sequence()
            ->push(['data' => [helloAssoTicket()], 'pagination' => ['totalPages' => 1]])
            ->push([], 503),
    ]);
    $tickets = app(ConcertTickets::class);
    $tickets->tickets();

    $this->travel(61)->seconds();

    $list = $tickets->tickets();
    expect($list->tickets)->toHaveCount(1)->and($list->isStale)->toBeTrue();
});

test('without any known list a failure is reported to the caller', function () {
    fakeTicketing([], 503);

    app(ConcertTickets::class)->tickets();
})->throws(HelloAssoUnavailable::class);

test('the event is read with its date, place and prices', function () {
    fakeTicketing([]);

    $event = app(ConcertTickets::class)->event();

    expect($event['title'])->toBe('Orchestre des élèves')
        ->and($event['startsAt']->toIso8601String())->toBe('2026-11-27T20:00:00+01:00')
        ->and($event['place'])->toBe('Salle Odilon')
        ->and($event['tiers'])->toBe([['label' => 'Adulte', 'priceCents' => 1000], ['label' => 'Enfant', 'priceCents' => 500]]);
});

test('the event is null when HelloAsso fails', function () {
    fakeTicketing([], 503);

    expect(app(ConcertTickets::class)->event())->toBeNull();
});

test('the ticketing is connected only with API keys and a form', function () {
    $tickets = app(ConcertTickets::class);
    expect($tickets->isConnected())->toBeTrue()
        ->and($tickets->widgetUrl())->toBe('https://www.helloasso.com/associations/ecole/evenements/orchestre/widget');

    config(['trees.helloasso.client_id' => null]);
    expect($tickets->isConnected())->toBeFalse();

    config(['concert.display' => 'link']);
    expect($tickets->widgetUrl())->toBeNull()
        ->and($tickets->ticketingUrl())->toBe('https://www.helloasso.com/associations/ecole/evenements/orchestre');
});

test('HelloAsso is not called again for a while after a failure', function () {
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/forms/Event/orchestre/items*' => Http::sequence()
            ->push(['data' => [helloAssoTicket()], 'pagination' => ['totalPages' => 1]])
            ->push([], 503)
            ->push(['data' => [helloAssoTicket(), helloAssoTicket(['id' => 2])], 'pagination' => ['totalPages' => 1]]),
    ]);
    $tickets = app(ConcertTickets::class);
    $tickets->tickets();

    $this->travel(61)->seconds();
    expect($tickets->tickets()->isStale)->toBeTrue();
    expect($tickets->tickets()->isStale)->toBeTrue();
    Http::assertSentCount(3); // token, items, failed items: no retry yet

    $this->travel(61)->seconds();
    expect($tickets->tickets())->isStale->toBeFalse()->tickets->toHaveCount(2);
});

test('without any known list, a failure is not retried on every request', function () {
    fakeTicketing([], 503);
    $tickets = app(ConcertTickets::class);

    expect(fn () => $tickets->tickets())->toThrow(HelloAssoUnavailable::class)
        ->and(fn () => $tickets->tickets())->toThrow(HelloAssoUnavailable::class);
    Http::assertSentCount(2); // token + one items call
});

test('an unexpected answer keeps the last known list', function () {
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/forms/Event/orchestre/items*' => Http::sequence()
            ->push(['data' => [helloAssoTicket()], 'pagination' => ['totalPages' => 1]])
            ->push('<html>Maintenance</html>', 200),
    ]);
    $tickets = app(ConcertTickets::class);
    $tickets->tickets();

    $this->travel(61)->seconds();

    expect($tickets->tickets())->isStale->toBeTrue()->tickets->toHaveCount(1);
});

test('only the fields shown are kept in the cache', function () {
    fakeTicketing([helloAssoTicket(['payer' => ['address' => '1 rue des Lilas']])]);

    app(ConcertTickets::class)->tickets();

    expect(json_encode(Cache::get('concert.tickets')))->not->toContain('Lilas')->not->toContain('qrCode');
});

test('the event is not asked again for a while after a failure', function () {
    fakeTicketing([], 503);
    $tickets = app(ConcertTickets::class);

    expect($tickets->event())->toBeNull()->and($tickets->event())->toBeNull();
    Http::assertSentCount(2); // token + one form call
});

test('a tier without label or price does not break the event', function () {
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/forms/Event/orchestre/public' => Http::response(['title' => 'Orchestre', 'tiers' => [['id' => 1], 'x']]),
    ]);

    expect(app(ConcertTickets::class)->event()['tiers'])->toBe([['label' => '', 'priceCents' => 0]]);
});
