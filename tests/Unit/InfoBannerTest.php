<?php

use App\Support\InfoBanner;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config(['marche.timezone' => 'Europe/Paris']);
    // 14:00 in Paris (UTC+1 in November).
    $this->travelTo(Carbon::parse('2026-11-20 13:00:00', 'UTC'));
});

function banner(?string $message, ?string $start = null, ?string $end = null): InfoBanner
{
    config(['marche.info_banner' => ['message' => $message, 'start' => $start, 'end' => $end]]);

    return new InfoBanner;
}

test('the banner is hidden without a message', function () {
    expect(banner(null)->message())->toBeNull()
        ->and(banner('  ')->message())->toBeNull();
});

test('a message without dates is always shown', function () {
    expect(banner('Maintenance ce soir')->message())->toBe('Maintenance ce soir');
});

test('the banner is shown only between its start and end, in the market timezone', function (?string $start, ?string $end, bool $shown) {
    expect(banner('Maintenance', $start, $end)->message() !== null)->toBe($shown);
})->with([
    'inside the period' => ['2026-11-20 13:00', '2026-11-20 15:00', true],
    'at the exact start' => ['2026-11-20 14:00', '2026-11-20 15:00', true],
    'before the start' => ['2026-11-20 14:01', null, false],
    'after the end' => [null, '2026-11-20 13:59', false],
    'at the exact end' => [null, '2026-11-20 14:00', false],
    'start only, passed' => ['2026-11-19 08:00', null, true],
    'end only, upcoming' => [null, '2026-11-21 08:00', true],
]);

test('a malformed date hides the banner rather than showing it forever', function () {
    expect(banner('Maintenance', '20/11/2026', null)->message())->toBeNull()
        ->and(banner('Maintenance', null, '2026-11-21')->message())->toBeNull();
});
