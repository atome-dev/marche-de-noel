<?php

use App\Models\Slot;
use App\Models\Stand;
use App\Planning\PlanningCell;
use App\Planning\PlanningGrid;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

function standWithSlots(string $name, array $times): Stand
{
    $stand = (new Stand)->forceFill(['id' => crc32($name), 'name' => $name]);
    $stand->setRelation('slots', collect($times)->map(
        fn (array $time, int $index) => (new Slot)->forceFill([
            'id' => crc32($name) + $index,
            'date' => $time[0],
            'starts_at' => $time[1],
            'ends_at' => $time[2],
            'capacity' => 2,
        ])->setRelation('registrations', collect())
    ));

    return $stand;
}

function gridFor(array $stands, ?string $day = null, ?Closure $rowFilter = null): PlanningGrid
{
    return new PlanningGrid(collect($stands), $day, hourMin: '10:00', hourMax: '13:00', rowFilter: $rowFilter);
}

/** @return list<array{string, int, bool}> */
function describeCells(array $cells): array
{
    return array_map(fn (PlanningCell $cell) => [$cell->time, $cell->span, $cell->slot !== null], $cells);
}

test('columns always cover the planning hours in 30 minute steps', function () {
    $grid = gridFor([standWithSlots('Buvette', [['2026-11-28', '10:00', '11:00']])]);

    expect($grid->times())->toBe(['10:00', '10:30', '11:00', '11:30', '12:00', '12:30']);
});

test('there is one table per day, in chronological order', function () {
    $grid = gridFor([
        standWithSlots('Buvette', [['2026-11-29', '10:00', '11:00'], ['2026-11-28', '10:00', '11:00']]),
    ]);

    expect($grid->days()->keys()->all())->toBe(['2026-11-28', '2026-11-29']);
});

test('a day only lists the stands open that day', function () {
    $grid = gridFor([
        standWithSlots('Buvette', [['2026-11-28', '10:00', '11:00'], ['2026-11-29', '10:00', '11:00']]),
        standWithSlots('Crêpes', [['2026-11-29', '11:00', '12:00']]),
    ]);

    $names = fn (string $date) => $grid->days()[$date]->rows->map(fn ($row) => $row->stand->name)->all();

    expect($names('2026-11-28'))->toBe(['Buvette'])
        ->and($names('2026-11-29'))->toBe(['Buvette', 'Crêpes']);
});

test('a slot starts at its own column and spans its duration', function () {
    $grid = gridFor([
        standWithSlots('Buvette', [['2026-11-28', '10:30', '12:00'], ['2026-11-28', '12:30', '13:00']]),
    ]);

    $cells = $grid->days()['2026-11-28']->rows->first()->cells;

    expect(describeCells($cells))->toBe([
        ['10:00', 1, false],
        ['10:30', 3, true],
        ['12:00', 1, false],
        ['12:30', 1, true],
    ])->and(array_sum(array_map(fn (PlanningCell $cell) => $cell->span, $cells)))->toBe(6);
});

test('slots are placed in time order whatever their creation order', function () {
    $grid = gridFor([
        standWithSlots('Buvette', [['2026-11-28', '12:00', '13:00'], ['2026-11-28', '10:00', '11:00']]),
    ]);

    $cells = $grid->days()['2026-11-28']->rows->first()->cells;

    expect(describeCells($cells))->toBe([
        ['10:00', 2, true],
        ['11:00', 1, false],
        ['11:30', 1, false],
        ['12:00', 2, true],
    ]);
});

test('slots outside the planning hours are clamped to the grid', function () {
    $grid = gridFor([
        standWithSlots('Buvette', [['2026-11-28', '09:00', '10:30'], ['2026-11-28', '12:00', '14:00']]),
    ]);

    $cells = $grid->days()['2026-11-28']->rows->first()->cells;

    expect(describeCells($cells))->toBe([
        ['10:00', 1, true],
        ['10:30', 1, false],
        ['11:00', 1, false],
        ['11:30', 1, false],
        ['12:00', 2, true],
    ]);
});

test('the grid can be restricted to one day', function () {
    $grid = gridFor([
        standWithSlots('Buvette', [['2026-11-28', '10:00', '11:00'], ['2026-11-29', '10:00', '11:00']]),
    ], day: '2026-11-29');

    expect($grid->days()->keys()->all())->toBe(['2026-11-29']);
});

test('rows can be filtered per stand and day', function () {
    $grid = gridFor([
        standWithSlots('Buvette', [['2026-11-28', '10:00', '11:00'], ['2026-11-29', '10:00', '11:00']]),
        standWithSlots('Crêpes', [['2026-11-28', '11:00', '12:00']]),
    ], rowFilter: fn (Stand $stand, Collection $slots) => $stand->name === 'Buvette' && $slots->first()->date->toDateString() === '2026-11-29');

    expect($grid->days()->keys()->all())->toBe(['2026-11-29'])
        ->and($grid->isEmpty())->toBeFalse();
});

test('an empty grid is reported', function () {
    expect(gridFor([])->isEmpty())->toBeTrue();
});
