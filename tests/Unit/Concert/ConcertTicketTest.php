<?php

use App\Concert\ConcertTicket;
use App\Concert\TicketList;

test('a processed ticket is read', function () {
    $ticket = ConcertTicket::fromHelloAsso(helloAssoTicket(['user' => ['firstName' => 'Léo', 'lastName' => 'Martin'], 'isScanned' => true]));

    expect($ticket)
        ->id->toBe(110885)
        ->tier->toBe('Adulte')
        ->amountCents->toBe(1000)
        ->holderName->toBe('Léo Martin')
        ->payerName->toBe('Camille Martin')
        ->payerEmail->toBe('camille@example.org')
        ->ticketUrl->toBe('https://www.helloasso-sandbox.com/ticket?ticketId=110885')
        ->isScanned->toBeTrue()
        ->and($ticket->orderedAt->toIso8601String())->toBe('2026-10-16T23:32:36+02:00');
});

test('unprocessed and refunded tickets are ignored', function (array $overrides) {
    expect(ConcertTicket::fromHelloAsso(helloAssoTicket($overrides)))->toBeNull();
})->with([
    'not processed' => [['state' => 'Canceled']],
    'refunded payment' => [['payments' => [['state' => 'Refunded']]]],
    'refund operation' => [['payments' => [['refundOperations' => [['id' => 1]]]]]],
]);

test('the tickets of a payer are found whatever the case and spaces', function () {
    $list = new TicketList([
        ConcertTicket::fromHelloAsso(helloAssoTicket(['id' => 1, 'payer' => ['email' => 'Camille@Example.org ']])),
        ConcertTicket::fromHelloAsso(helloAssoTicket(['id' => 2, 'payer' => ['email' => 'autre@example.org']])),
    ], isStale: false);

    expect(array_map(fn (ConcertTicket $ticket) => $ticket->id, $list->forEmail(' camille@example.ORG')))->toBe([1]);
});

test('the summary counts tickets, amounts and entries by tier', function () {
    $list = new TicketList([
        ConcertTicket::fromHelloAsso(helloAssoTicket(['id' => 1, 'name' => 'Adulte', 'amount' => 1000, 'isScanned' => true])),
        ConcertTicket::fromHelloAsso(helloAssoTicket(['id' => 2, 'name' => 'Adulte', 'amount' => 1000])),
        ConcertTicket::fromHelloAsso(helloAssoTicket(['id' => 3, 'name' => 'Enfant', 'amount' => 500, 'isScanned' => true])),
    ], isStale: false);

    expect($list->summary())->toBe([
        'tiers' => [
            ['tier' => 'Adulte', 'count' => 2, 'amountCents' => 2000],
            ['tier' => 'Enfant', 'count' => 1, 'amountCents' => 500],
        ],
        'count' => 3,
        'amountCents' => 2500,
        'scanned' => 2,
    ]);
});
