<?php

use App\Exceptions\RegistrationRefused;
use App\Models\Registration;
use App\Models\SchoolClass;
use App\Models\Slot;
use App\Models\Stand;
use App\Models\User;
use App\Planning\SlotRegistrar;

beforeEach(function () {
    $this->registrar = app(SlotRegistrar::class);
    $this->user = User::factory()->create(['email' => 'camille@example.com']);
});

function refusal(callable $callback): ?string
{
    try {
        $callback();
    } catch (RegistrationRefused $exception) {
        return $exception->reason;
    }

    return null;
}

test('a volunteer can register on a free slot', function () {
    $slot = Slot::factory()->create();

    $registration = $this->registrar->register($this->user, $slot, 'Je peux apporter la monnaie');

    expect($registration->user_id)->toBe($this->user->id)
        ->and($registration->message)->toBe('Je peux apporter la monnaie')
        ->and($slot->registrations()->count())->toBe(1);
});

test('a full slot is refused', function () {
    $slot = Slot::factory()->create(['capacity' => 1]);
    Registration::factory()->for($slot)->create();

    expect(refusal(fn () => $this->registrar->register($this->user, $slot)))->toBe('full');
});

test('registering twice is refused', function () {
    $slot = Slot::factory()->create();
    $this->registrar->register($this->user, $slot);

    expect(refusal(fn () => $this->registrar->register($this->user, $slot)))->toBe('already_registered');
});

test('an inactive stand is refused', function () {
    $slot = Slot::factory()->for(Stand::factory()->inactive())->create();

    expect(refusal(fn () => $this->registrar->register($this->user, $slot)))->toBe('inactive');
});

describe('reserved slots', function () {
    test('are refused to people outside the reservation', function () {
        $slot = Slot::factory()->reservedFor(['other@example.com'])->create();

        expect(refusal(fn () => $this->registrar->register($this->user, $slot)))->toBe('reserved');
    });

    test('accept people listed by email, whatever the case', function () {
        $slot = Slot::factory()->reservedFor(['camille@example.com'])->create();
        $this->user->update(['email' => 'camille@example.com']);

        expect(refusal(fn () => $this->registrar->register($this->user, $slot)))->toBeNull();
    });

    test('accept people of a reserved class', function () {
        $class = SchoolClass::query()->first();
        $this->user->schoolClasses()->attach($class);
        $slot = Slot::factory()->create(['is_reserved' => true]);
        $slot->reservedClasses()->attach($class);

        expect(refusal(fn () => $this->registrar->register($this->user->fresh(), $slot)))->toBeNull();
    });

    test('are open to everyone once the reservation is lifted', function () {
        $slot = Slot::factory()->reservedFor(['other@example.com'])->create(['is_reserved' => false]);

        expect(refusal(fn () => $this->registrar->register($this->user, $slot)))->toBeNull();
    });
});

describe('stands linked to classes', function () {
    beforeEach(function () {
        $this->class = SchoolClass::query()->first();
        $this->stand = Stand::factory()->create();
        $this->stand->schoolClasses()->attach($this->class);
        $this->slot = Slot::factory()->for($this->stand)->create();
    });

    test('refuse volunteers from other classes', function () {
        expect(refusal(fn () => $this->registrar->register($this->user, $this->slot)))->toBe('other_classes');
    });

    test('accept volunteers from these classes', function () {
        $this->user->schoolClasses()->attach($this->class);

        expect(refusal(fn () => $this->registrar->register($this->user->fresh(), $this->slot)))->toBeNull();
    });

    test('accept everyone when opened to all', function () {
        $this->stand->update(['is_open_to_all' => true]);

        expect(refusal(fn () => $this->registrar->register($this->user, $this->slot->fresh())))->toBeNull();
    });
});

test('overlapping registrations are reported but allowed', function () {
    $morning = Slot::factory()->at('2026-11-28', '10:00', '12:00')->create();
    $this->registrar->register($this->user, $morning);

    $overlapping = Slot::factory()->at('2026-11-28', '11:00', '13:00')->create();
    $adjacent = Slot::factory()->at('2026-11-28', '12:00', '14:00')->create();
    $otherDay = Slot::factory()->at('2026-11-29', '10:00', '12:00')->create();

    expect($this->registrar->overlapsFor($this->user, $overlapping)->pluck('id')->all())->toBe([$morning->id])
        ->and($this->registrar->overlapsFor($this->user, $adjacent))->toBeEmpty()
        ->and($this->registrar->overlapsFor($this->user, $otherDay))->toBeEmpty();

    expect(refusal(fn () => $this->registrar->register($this->user, $overlapping)))->toBeNull();
});

test('a volunteer can unregister', function () {
    $slot = Slot::factory()->create();
    $this->registrar->register($this->user, $slot);

    $this->registrar->unregister($this->user, $slot);

    expect($slot->registrations()->count())->toBe(0);
});

test('a guest without account can be added until the slot is full', function () {
    $slot = Slot::factory()->create(['capacity' => 1]);

    $registration = $this->registrar->addGuest($slot, 'Mamie Jeanne');

    expect($registration->isGuest())->toBeTrue()
        ->and($registration->displayName())->toBe('Mamie Jeanne')
        ->and(refusal(fn () => $this->registrar->addGuest($slot->fresh(), 'Papi Jean')))->toBe('full');
});

test('the refusal reason can be computed without registering', function () {
    $slot = Slot::factory()->create(['capacity' => 0]);

    expect($this->registrar->refusalFor($this->user, $slot))->toBe('full')
        ->and($slot->registrations()->count())->toBe(0);
});
