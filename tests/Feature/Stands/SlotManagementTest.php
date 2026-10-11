<?php

use App\Livewire\Stands\SlotEditor;
use App\Models\Registration;
use App\Models\SchoolClass;
use App\Models\Slot;
use App\Models\Stand;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'marche.dates' => ['2026-11-28', '2026-11-29'],
        'marche.planning.hour_min' => '09:00',
        'marche.planning.hour_max' => '19:00',
        'marche.planning.durations' => [30, 60, 90, 120],
    ]);

    $this->manager = User::factory()->create();
    $this->stand = Stand::factory()->create();
    $this->stand->managers()->attach($this->manager);
    $this->actingAs($this->manager);
});

test('a manager adds a slot', function () {
    Livewire::test(SlotEditor::class, ['stand' => $this->stand])
        ->call('create')
        ->set('date', '2026-11-28')
        ->set('starts_at', '10:00')
        ->set('duration', 90)
        ->set('capacity', 3)
        ->set('label', 'Montage')
        ->call('save')
        ->assertHasNoErrors();

    $slot = $this->stand->slots()->sole();

    expect($slot->starts_at)->toBe('10:00')
        ->and($slot->ends_at)->toBe('11:30')
        ->and($slot->capacity)->toBe(3)
        ->and($slot->label)->toBe('Montage')
        ->and($slot->is_reserved)->toBeFalse();
});

test('slots must fit the market dates and planning hours', function (string $date, string $start, int $duration, string $field) {
    Livewire::test(SlotEditor::class, ['stand' => $this->stand])
        ->call('create')
        ->set('date', $date)
        ->set('starts_at', $start)
        ->set('duration', $duration)
        ->set('capacity', 2)
        ->call('save')
        ->assertHasErrors([$field]);

    expect($this->stand->slots()->count())->toBe(0);
})->with([
    'unknown date' => ['2026-12-01', '10:00', 120, 'date'],
    'too early' => ['2026-11-28', '08:00', 120, 'starts_at'],
    'ends after closing' => ['2026-11-28', '18:00', 90, 'duration'],
    'unknown duration' => ['2026-11-28', '10:00', 45, 'duration'],
    'start not on a half hour' => ['2026-11-28', '10:15', 60, 'starts_at'],
]);

describe('overlapping slots of the same stand and day', function () {
    beforeEach(function () {
        $this->existing = Slot::factory()->for($this->stand)->at('2026-11-28', '10:00', '12:00')->create();
    });

    test('are refused', function (string $start, int $duration) {
        Livewire::test(SlotEditor::class, ['stand' => $this->stand])
            ->call('create')
            ->set('date', '2026-11-28')
            ->set('starts_at', $start)
            ->set('duration', $duration)
            ->call('save')
            ->assertHasErrors(['starts_at']);

        expect($this->stand->slots()->count())->toBe(1);
    })->with([
        'same time' => ['10:00', 120],
        'starts inside' => ['11:30', 60],
        'ends inside' => ['09:00', 90],
        'inside' => ['10:30', 30],
    ]);

    test('do not concern adjacent slots, other days or other stands', function () {
        $editor = Livewire::test(SlotEditor::class, ['stand' => $this->stand]);

        foreach ([['2026-11-28', '12:00'], ['2026-11-28', '09:00'], ['2026-11-29', '10:00']] as [$date, $start]) {
            $editor->call('create')
                ->set('date', $date)
                ->set('starts_at', $start)
                ->set('duration', 60)
                ->call('save')
                ->assertHasNoErrors();
        }

        $other = Stand::factory()->create();
        $other->managers()->attach($this->manager);

        Livewire::test(SlotEditor::class, ['stand' => $other])
            ->call('create')
            ->set('date', '2026-11-28')
            ->set('starts_at', '10:00')
            ->set('duration', 120)
            ->call('save')
            ->assertHasNoErrors();
    });

    test('do not prevent editing the slot itself', function () {
        Livewire::test(SlotEditor::class, ['stand' => $this->stand])
            ->call('edit', $this->existing->id)
            ->set('starts_at', '10:30')
            ->set('duration', 90)
            ->call('save')
            ->assertHasNoErrors();

        expect($this->existing->refresh()->starts_at)->toBe('10:30');
    });
});

test('a slot may end exactly at the closing hour', function () {
    Livewire::test(SlotEditor::class, ['stand' => $this->stand])
        ->call('create')
        ->set('date', '2026-11-29')
        ->set('starts_at', '17:00')
        ->set('duration', 120)
        ->call('save')
        ->assertHasNoErrors();

    expect($this->stand->slots()->sole()->ends_at)->toBe('19:00');
});

test('a slot can be reserved to emails and classes, then opened to all', function () {
    $class = SchoolClass::query()->first();

    $component = Livewire::test(SlotEditor::class, ['stand' => $this->stand])
        ->call('create')
        ->set('date', '2026-11-28')
        ->set('starts_at', '10:00')
        ->set('duration', 120)
        ->set('capacity', 2)
        ->set('is_reserved', true)
        ->set('reservedEmails', "Camille@Example.com\n dominique@example.com ; ")
        ->set('reservedClassIds', [$class->id])
        ->call('save')
        ->assertHasNoErrors();

    $slot = $this->stand->slots()->sole();

    expect($slot->is_reserved)->toBeTrue()
        ->and($slot->reserved_emails)->toBe(['camille@example.com', 'dominique@example.com'])
        ->and($slot->reservedClasses->pluck('id')->all())->toBe([$class->id]);

    $component->call('openToAll', $slot->id);

    expect($slot->refresh()->is_reserved)->toBeFalse()
        ->and($slot->reserved_emails)->toBe(['camille@example.com', 'dominique@example.com']);
});

test('invalid reserved emails are rejected', function () {
    Livewire::test(SlotEditor::class, ['stand' => $this->stand])
        ->call('create')
        ->set('date', '2026-11-28')
        ->set('starts_at', '10:00')
        ->set('duration', 120)
        ->set('is_reserved', true)
        ->set('reservedEmails', 'pas-un-email')
        ->call('save')
        ->assertHasErrors(['reservedEmails']);
});

test('a slot can be edited', function () {
    $slot = Slot::factory()->for($this->stand)->create(['capacity' => 2]);

    Livewire::test(SlotEditor::class, ['stand' => $this->stand])
        ->call('edit', $slot->id)
        ->assertSet('starts_at', '10:00')
        ->assertSet('duration', 120)
        ->set('duration', 30)
        ->set('capacity', 5)
        ->call('save')
        ->assertHasNoErrors();

    expect($slot->refresh()->capacity)->toBe(5)
        ->and($slot->ends_at)->toBe('10:30');
});

test('the capacity cannot go below the registered volunteers', function () {
    $slot = Slot::factory()->for($this->stand)->create(['capacity' => 3]);
    Registration::factory()->count(2)->for($slot)->create();

    Livewire::test(SlotEditor::class, ['stand' => $this->stand])
        ->call('edit', $slot->id)
        ->set('capacity', 1)
        ->call('save')
        ->assertHasErrors(['capacity']);
});

test('a slot can be deleted with its registrations', function () {
    $slot = Slot::factory()->for($this->stand)->create();
    Registration::factory()->for($slot)->create();

    Livewire::test(SlotEditor::class, ['stand' => $this->stand])->call('delete', $slot->id);

    expect($slot->fresh())->toBeNull();
    $this->assertDatabaseCount('registrations', 0);
});

test('slots of another stand cannot be touched', function () {
    $foreign = Slot::factory()->create();

    Livewire::test(SlotEditor::class, ['stand' => $this->stand])
        ->call('delete', $foreign->id)
        ->assertNotFound();

    expect($foreign->fresh())->not->toBeNull();
});

test('volunteers cannot manage slots', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(SlotEditor::class, ['stand' => $this->stand])->assertForbidden();
});
