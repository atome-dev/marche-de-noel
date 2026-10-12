<?php

use App\Livewire\Planning\Board;
use App\Models\Registration;
use App\Models\SchoolClass;
use App\Models\Slot;
use App\Models\Stand;
use App\Models\User;
use App\Planning\PlanningFingerprint;
use App\Planning\PlanningStats;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'marche.dates' => ['2026-11-28', '2026-11-29'],
        'marche.planning.hour_min' => '09:00',
        'marche.planning.hour_max' => '19:00',
    ]);

    $this->user = User::factory()->create(['first_name' => 'Camille', 'last_name' => 'Martin']);

    $this->buvette = Stand::factory()->create(['name' => 'Buvette', 'position' => 1]);
    $this->buvetteSlot = Slot::factory()->for($this->buvette)->at('2026-11-28', '10:00', '12:00')->create(['capacity' => 2]);

    $this->crepes = Stand::factory()->create(['name' => 'Crêpes', 'position' => 2]);
    $this->crepesSlot = Slot::factory()->for($this->crepes)->at('2026-11-29', '14:00', '16:00')->create(['capacity' => 1]);

    $this->actingAs($this->user);
});

describe('grid', function () {
    test('the planning page renders', function () {
        $this->get(route('planning'))
            ->assertOk()
            ->assertSeeLivewire(Board::class)
            ->assertSee('Buvette')
            ->assertSee('Crêpes');
    });

    test('inactive stands and stands without places are hidden', function () {
        Slot::factory()->for(Stand::factory()->inactive()->create(['name' => 'Ancien stand']))->create();
        Slot::factory()->for(Stand::factory()->create(['name' => 'Stand sans bénévole']))->create(['capacity' => 0]);

        Livewire::test(Board::class)
            ->assertDontSee('Ancien stand')
            ->assertDontSee('Stand sans bénévole');
    });

    test('there is one table per day listing only the stands open that day', function () {
        Livewire::test(Board::class)
            ->assertSeeHtmlInOrder([
                'data-day="2026-11-28"',
                'data-stand="'.$this->buvette->id.'"',
                'data-day="2026-11-29"',
                'data-stand="'.$this->crepes->id.'"',
            ]);

        expect(substr_count(Livewire::test(Board::class)->html(), 'data-stand="'.$this->buvette->id.'"'))->toBe(1);
    });

    test('columns cover the planning hours in 30 minute steps', function () {
        Livewire::test(Board::class)
            ->assertSeeHtml('data-time="09:00"')
            ->assertSeeHtml('data-time="18:30"')
            ->assertDontSeeHtml('data-time="19:00"')
            ->assertDontSeeHtml('data-time="08:30"');
    });

    test('a slot starts at its column and spans its duration', function () {
        Livewire::test(Board::class)
            ->assertSeeHtml('data-slot-start="10:00" colspan="4"')
            ->assertSeeHtml('data-slot-start="14:00" colspan="4"')
            ->assertSeeHtml('data-slot-empty');
    });

    test('the grid can be filtered by day', function () {
        Livewire::test(Board::class)
            ->set('day', '2026-11-29')
            ->assertDontSeeHtml('data-day="2026-11-28"')
            ->assertSeeHtml('data-day="2026-11-29"');
    });

    test('the grid can be filtered by stand', function () {
        Livewire::test(Board::class)
            ->set('standId', $this->crepes->id)
            ->assertSeeHtml('data-stand="'.$this->crepes->id.'"')
            ->assertDontSeeHtml('data-stand="'.$this->buvette->id.'"');
    });

    test('the grid can show only my registrations', function () {
        Registration::factory()->for($this->crepesSlot)->for($this->user)->create();

        Livewire::test(Board::class)
            ->set('availability', 'mine')
            ->assertSeeHtml('data-stand="'.$this->crepes->id.'"')
            ->assertDontSeeHtml('data-stand="'.$this->buvette->id.'"');
    });

    test('the grid can show only stands with free places', function () {
        Registration::factory()->for($this->crepesSlot)->create();

        Livewire::test(Board::class)
            ->set('availability', 'free')
            ->assertSeeHtml('data-stand="'.$this->buvette->id.'"')
            ->assertDontSeeHtml('data-stand="'.$this->crepes->id.'"');
    });

    test('reserved slots show a lock to other people', function () {
        $this->buvetteSlot->update(['is_reserved' => true, 'reserved_emails' => ['other@example.com']]);

        Livewire::test(Board::class)->assertSeeHtml('data-slot-reserved');
    });
});

describe('registration', function () {
    test('a volunteer registers with an optional message', function () {
        Livewire::test(Board::class)
            ->call('openSlot', $this->buvetteSlot->id)
            ->set('message', 'J’apporte la monnaie')
            ->call('register')
            ->assertHasNoErrors();

        $registration = $this->buvetteSlot->registrations()->sole();

        expect($registration->user_id)->toBe($this->user->id)
            ->and($registration->message)->toBe('J’apporte la monnaie');
    });

    test('a refused registration shows the reason', function () {
        $this->buvetteSlot->update(['is_reserved' => true, 'reserved_emails' => ['other@example.com']]);

        Livewire::test(Board::class)
            ->call('openSlot', $this->buvetteSlot->id)
            ->call('register')
            ->assertHasErrors(['slot']);

        expect($this->buvetteSlot->registrations()->count())->toBe(0);
    });

    test('an overlapping registration is announced in the slot details', function () {
        $other = Slot::factory()->for(Stand::factory()->create(['name' => 'Chamboule-tout']))
            ->at('2026-11-28', '11:00', '13:00')->create();
        Registration::factory()->for($other)->for($this->user)->create();

        Livewire::test(Board::class)
            ->call('openSlot', $this->buvetteSlot->id)
            ->assertSee(__('planning.modal.overlap_warning'))
            ->assertSee('Chamboule-tout');
    });

    test('a volunteer can unregister', function () {
        Registration::factory()->for($this->buvetteSlot)->for($this->user)->create();

        Livewire::test(Board::class)
            ->call('openSlot', $this->buvetteSlot->id)
            ->call('unregister');

        expect($this->buvetteSlot->registrations()->count())->toBe(0);
    });
});

describe('privacy', function () {
    beforeEach(function () {
        $this->volunteer = User::factory()->create([
            'first_name' => 'Dominique',
            'last_name' => 'Petit',
            'phone' => '06 98 76 54 32',
        ]);
        Registration::factory()->for($this->buvetteSlot)->for($this->volunteer)->create(['message' => 'Message privé']);
    });

    test('volunteers see short names but neither phones nor messages', function () {
        Livewire::test(Board::class)
            ->call('openSlot', $this->buvetteSlot->id)
            ->assertSee('Dominique P.')
            ->assertDontSee('Petit')
            ->assertDontSee('06 98 76 54 32')
            ->assertDontSee('Message privé');
    });

    test('managers see full names, phones and messages of their stand', function () {
        $this->buvette->managers()->attach($this->user);

        Livewire::test(Board::class)
            ->call('openSlot', $this->buvetteSlot->id)
            ->assertSee('Dominique Petit')
            ->assertSee('06 98 76 54 32')
            ->assertSee('Message privé');
    });
});

describe('manager actions', function () {
    test('a manager adds a person without account and removes a registration', function () {
        $this->buvette->managers()->attach($this->user);
        $registration = Registration::factory()->for($this->buvetteSlot)->create();

        Livewire::test(Board::class)
            ->call('openSlot', $this->buvetteSlot->id)
            ->set('guestName', 'Mamie Jeanne')
            ->call('addGuest')
            ->assertHasNoErrors()
            ->call('removeRegistration', $registration->id);

        expect($this->buvetteSlot->registrations()->pluck('guest_name')->all())->toBe(['Mamie Jeanne']);
    });

    test('a manager opens a reserved slot to everyone', function () {
        $this->buvette->managers()->attach($this->user);
        $this->buvetteSlot->update(['is_reserved' => true, 'reserved_emails' => ['other@example.com']]);

        Livewire::test(Board::class)
            ->call('openSlot', $this->buvetteSlot->id)
            ->call('openToAll');

        expect($this->buvetteSlot->refresh()->is_reserved)->toBeFalse();
    });

    test('volunteers cannot use manager actions', function () {
        $registration = Registration::factory()->for($this->buvetteSlot)->create();

        Livewire::test(Board::class)
            ->call('openSlot', $this->buvetteSlot->id)
            ->call('removeRegistration', $registration->id)
            ->assertForbidden();

        expect($registration->fresh())->not->toBeNull();
    });
});

describe('print', function () {
    test('the global planning can be printed', function () {
        $this->get(route('planning.print'))
            ->assertOk()
            ->assertSee('Buvette')
            ->assertSee('Crêpes')
            ->assertSeeHtml('colspan="4"');
    });

    test('a single stand can be printed with phones for its managers only', function () {
        $volunteer = User::factory()->create(['phone' => '06 98 76 54 32']);
        Registration::factory()->for($this->buvetteSlot)->for($volunteer)->create();

        $this->get(route('planning.print', ['stand' => $this->buvette->id]))
            ->assertOk()
            ->assertSee('Buvette')
            ->assertDontSee('Crêpes')
            ->assertDontSee('06 98 76 54 32');

        $this->buvette->managers()->attach($this->user);

        $this->get(route('planning.print', ['stand' => $this->buvette->id]))
            ->assertSee('06 98 76 54 32');
    });
});

describe('preparation days', function () {
    beforeEach(function () {
        Slot::factory()->for($this->buvette)->at('2026-11-26', '17:00', '19:00')->create(['capacity' => 3]);
    });

    test('a day before the market gets its own table, first and flagged', function () {
        Livewire::test(Board::class)
            ->assertSeeInOrder(['data-day="2026-11-26"', __('planning.preparation'), 'data-day="2026-11-28"', 'data-day="2026-11-29"'], false)
            ->assertSet('days', ['2026-11-26', '2026-11-28', '2026-11-29']);
    });

    test('market days are not flagged as preparations', function () {
        Livewire::test(Board::class)
            ->set('day', '2026-11-28')
            ->assertDontSee(__('planning.preparation'));
    });

    test('the planning can be filtered on a preparation day', function () {
        Livewire::test(Board::class)
            ->set('day', '2026-11-26')
            ->assertSee('data-day="2026-11-26"', false)
            ->assertDontSee('data-day="2026-11-28"', false)
            ->assertDontSee('data-stand="'.$this->crepes->id.'"', false);
    });

    test('volunteers register on a preparation slot like on any other', function () {
        $slot = $this->buvette->slots()->whereDate('date', '2026-11-26')->sole();

        Livewire::test(Board::class)->call('openSlot', $slot->id)->call('register')->assertHasNoErrors();

        expect($slot->registrations()->where('user_id', $this->user->id)->exists())->toBeTrue();
    });

    test('a preparation day can be printed', function () {
        $this->get(route('planning.print', ['day' => '2026-11-26']))
            ->assertOk()
            ->assertSee(__('planning.preparation'))
            ->assertSee('Buvette')
            ->assertDontSee('Crêpes');
    });
});

describe('automatic refresh', function () {
    test('the planning polls at the configured interval', function () {
        config(['marche.planning.refresh_seconds' => 20]);

        Livewire::test(Board::class)->assertSeeHtml('wire:poll.20s="refreshIfChanged"');
    });

    test('polling is disabled when the interval is zero', function () {
        config(['marche.planning.refresh_seconds' => 0]);

        Livewire::test(Board::class)->assertDontSeeHtml('wire:poll');
    });

    test('a poll does not redraw an unchanged planning', function () {
        $component = Livewire::test(Board::class);
        $fingerprint = $component->get('fingerprint');

        $component->call('refreshIfChanged')->assertSet('fingerprint', $fingerprint);

        expect($component->effects)->not->toHaveKey('html');
    });

    test('a poll shows a registration made by someone else', function () {
        $component = Livewire::test(Board::class)->assertDontSee('Dominique P.');

        Registration::factory()->for($this->buvetteSlot)
            ->for(User::factory()->create(['first_name' => 'Dominique', 'last_name' => 'Perrin']))
            ->create();

        $component->call('refreshIfChanged')->assertSee('Dominique P.');
    });

    test('the fingerprint changes with what the planning shows', function (Closure $change) {
        $fingerprint = app(PlanningFingerprint::class);
        $before = $fingerprint->current();

        expect($fingerprint->current())->toBe($before);

        $change($this);

        expect($fingerprint->current())->not->toBe($before);
    })->with([
        'a registration' => [fn ($test) => Registration::factory()->for($test->buvetteSlot)->for(User::factory())->create()],
        'a new slot' => [fn ($test) => Slot::factory()->for($test->crepes)->at('2026-11-28', '14:00', '15:00')->create()],
        'a deleted slot' => [fn ($test) => $test->crepesSlot->delete()],
        'a new manager' => [fn ($test) => $test->buvette->managers()->attach($test->user)],
        'a reserved class' => [fn ($test) => $test->buvetteSlot->reservedClasses()->attach(SchoolClass::factory()->create())],
    ]);
});

describe('past days', function () {
    test('days before today are left out of the planning', function () {
        $this->travelTo('2026-11-29 10:00');

        Livewire::test(Board::class)
            ->assertDontSeeHtml('data-day="2026-11-28"')
            ->assertSeeHtml('data-day="2026-11-29"')
            ->assertDontSeeHtml('wire:key="tab-2026-11-28"')
            ->assertSeeHtml('wire:key="tab-2026-11-29"');
    });

    test('the current day stays until midnight in the market time zone', function () {
        config(['marche.timezone' => 'Europe/Paris']);

        // 23:30 in Paris on the 28th.
        $this->travelTo('2026-11-28 22:30');
        Livewire::test(Board::class)->assertSeeHtml('data-day="2026-11-28"');

        // 00:30 in Paris on the 29th.
        $this->travelTo('2026-11-28 23:30');
        Livewire::test(Board::class)->assertDontSeeHtml('data-day="2026-11-28"');
    });

    test('the printable planning leaves out past days too', function () {
        $this->travelTo('2026-11-29 10:00');

        $this->get(route('planning.print'))
            ->assertOk()
            ->assertDontSee('Buvette')
            ->assertSee('Crêpes');
    });

    test('an open planning is redrawn when the day changes', function () {
        $this->travelTo('2026-11-28 12:00');
        $component = Livewire::test(Board::class)->assertSeeHtml('data-day="2026-11-28"');

        $this->travelTo('2026-11-29 12:00');
        $component->call('refreshIfChanged')->assertDontSeeHtml('data-day="2026-11-28"');
    });

    test('past registrations still count in the figures', function () {
        Registration::factory()->for($this->buvetteSlot)->for($this->user)->create();

        $this->travelTo('2026-12-05 10:00');

        expect((new PlanningStats)->volunteers())->toBe(1)
            ->and((new PlanningStats)->minutes())->toBe(120);
    });
});
