<?php

use App\TreeOrders\TreeCatalog;
use App\TreeOrders\TreeSize;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config([
        'marche.timezone' => 'Europe/Paris',
        'trees.sizes' => [
            ['key' => 'lutins', 'name' => 'Le sapin des lutins', 'height' => '80/100 cm', 'price' => 27],
            ['key' => 'moyen', 'name' => 'Le sapin moyen', 'height' => '125/150 cm', 'price' => 35.5],
        ],
        'trees.deadline' => '2026-11-29',
        'trees.pickup.date' => '2026-12-05',
        'trees.max_quantity' => 10,
    ]);

    $this->catalog = new TreeCatalog;
});

test('sizes are read from the configuration, prices in cents', function () {
    expect($this->catalog->sizes())->toEqual([
        'lutins' => new TreeSize('lutins', 'Le sapin des lutins', '80/100 cm', 2700),
        'moyen' => new TreeSize('moyen', 'Le sapin moyen', '125/150 cm', 3550),
    ])
        ->and($this->catalog->find('moyen')->priceCents)->toBe(3550)
        ->and($this->catalog->find('inconnu'))->toBeNull()
        ->and($this->catalog->maxQuantity())->toBe(10);
});

test('malformed sizes are skipped without breaking the catalogue', function () {
    Log::spy();

    config(['trees.sizes' => [
        ['key' => 'lutins', 'name' => 'Le sapin des lutins', 'height' => '80/100 cm'],
        ['key' => 'grand', 'name' => 'Le grand sapin', 'height' => '150/175 cm', 'price' => 'quarante'],
        ['key' => 'moyen', 'name' => 'Le sapin moyen', 'height' => '125/150 cm', 'price' => 35],
    ]]);

    expect(array_keys($this->catalog->sizes()))->toBe(['moyen']);

    Log::shouldHaveReceived('warning')->twice();
});

test('the deadline day stays open until midnight, Paris time', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-29 23:59:59', 'Europe/Paris'));
    expect($this->catalog->isClosed())->toBeFalse();

    $this->travelTo(CarbonImmutable::parse('2026-11-30 00:00:00', 'Europe/Paris'));
    expect($this->catalog->isClosed())->toBeTrue();
});

test('orders never close without a deadline', function () {
    config(['trees.deadline' => null]);

    expect($this->catalog->deadline())->toBeNull()
        ->and($this->catalog->isClosed())->toBeFalse();
});

test('the pickup date is read in the market timezone', function () {
    expect($this->catalog->pickupDate()->toDateString())->toBe('2026-12-05');

    config(['trees.pickup.date' => null]);
    expect($this->catalog->pickupDate())->toBeNull();
});
