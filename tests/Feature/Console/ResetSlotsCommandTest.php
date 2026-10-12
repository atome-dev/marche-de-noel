<?php

use App\Models\Registration;
use App\Models\SchoolClass;
use App\Models\Slot;
use App\Models\Stand;
use App\Models\User;

beforeEach(function () {
    config([
        'marche.dates' => ['2026-11-28', '2026-11-29'],
        'marche.planning.hour_min' => '09:00',
        'marche.planning.hour_max' => '20:00',
        'marche.planning.default_slots' => ['12:30-14:00', '14:00-16:00'],
        'marche.planning.default_capacity' => 3,
    ]);

    $this->stand = Stand::factory()->create();
    $this->stand->managers()->attach($this->manager = User::factory()->create());

    $slot = Slot::factory()->for($this->stand)->create(['date' => '2026-11-28', 'starts_at' => '10:00', 'ends_at' => '11:00']);
    $slot->reservedClasses()->attach(SchoolClass::query()->first());
    Registration::factory()->for($slot)->for(User::factory())->create();

    $this->confirmation = __('commands.reset_slots.confirm', ['word' => __('commands.reset_slots.word')]);
});

/** @return list<string> */
function slotRanges(Stand $stand): array
{
    return $stand->slots()->orderBy('date')->orderBy('starts_at')->get()
        ->map(fn (Slot $slot) => $slot->date->toDateString().' '.$slot->starts_at.'-'.$slot->ends_at)
        ->all();
}

test('nothing changes without the exact confirmation word', function () {
    $this->artisan('marche:reset-slots')
        ->expectsQuestion($this->confirmation, 'oui')
        ->expectsOutput(__('commands.reset_slots.aborted'))
        ->assertFailed();

    expect(slotRanges($this->stand))->toBe(['2026-11-28 10:00-11:00'])
        ->and(Registration::count())->toBe(1);
});

test('every slot is replaced by the default slots and registrations are deleted', function () {
    $this->artisan('marche:reset-slots')
        ->expectsQuestion($this->confirmation, __('commands.reset_slots.word'))
        ->assertSuccessful();

    expect(slotRanges($this->stand))->toBe([
        '2026-11-28 12:30-14:00', '2026-11-28 14:00-16:00',
        '2026-11-29 12:30-14:00', '2026-11-29 14:00-16:00',
    ])
        ->and($this->stand->slots()->pluck('capacity')->unique()->all())->toBe([3])
        ->and(Registration::count())->toBe(0)
        ->and($this->stand->managers()->count())->toBe(1)
        ->and(User::count())->toBe(2);

    $this->assertDatabaseCount('school_class_slot', 0);
});

test('a single stand can be reset', function () {
    $other = Stand::factory()->create();
    Slot::factory()->for($other)->create(['date' => '2026-11-28', 'starts_at' => '09:00', 'ends_at' => '10:00']);

    $this->artisan('marche:reset-slots', ['--stand' => $other->id])
        ->expectsQuestion($this->confirmation, __('commands.reset_slots.word'))
        ->assertSuccessful();

    expect($other->slots()->count())->toBe(4)
        ->and(slotRanges($this->stand))->toBe(['2026-11-28 10:00-11:00'])
        ->and(Registration::count())->toBe(1);
});

test('the command refuses to run without valid default slots or an existing stand', function () {
    $this->artisan('marche:reset-slots', ['--stand' => 999999])
        ->expectsOutput(__('commands.reset_slots.no_stand'))
        ->assertFailed();

    config(['marche.planning.default_slots' => ['08:00-21:00']]);

    $this->artisan('marche:reset-slots')
        ->expectsOutput(__('commands.reset_slots.no_defaults'))
        ->assertFailed();

    expect(slotRanges($this->stand))->toBe(['2026-11-28 10:00-11:00']);
});

test('the force option skips the confirmation', function () {
    $this->artisan('marche:reset-slots', ['--force' => true])
        ->doesntExpectOutput(__('commands.reset_slots.aborted'))
        ->assertSuccessful();

    expect($this->stand->slots()->count())->toBe(4)
        ->and(Registration::count())->toBe(0);
});
