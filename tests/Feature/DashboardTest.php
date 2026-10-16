<?php

use App\Models\Registration;
use App\Models\Slot;
use App\Models\Stand;
use App\Models\User;
use App\Planning\PlanningStats;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

describe('planning figures', function () {
    beforeEach(function () {
        $stand = Stand::factory()->create();
        $short = Slot::factory()->for($stand)->at('2026-11-28', '10:00', '11:30')->create(['capacity' => 5]);
        $long = Slot::factory()->for($stand)->at('2026-11-28', '14:00', '16:00')->create(['capacity' => 5]);

        $camille = User::factory()->create();

        // 2 × 1 h 30 + 1 × 2 h for accounts, Camille being counted once.
        Registration::factory()->for($short)->for($camille)->create();
        Registration::factory()->for($short)->for(User::factory())->create();
        Registration::factory()->for($long)->for($camille)->create();

        // 1 h 30 + 2 h for the same person without an account.
        Registration::factory()->for($short)->guest('Dominique Perrin')->create();
        Registration::factory()->for($long)->guest(' dominique  perrin')->create();

        // Ignored: inactive stand.
        Registration::factory()->for(Slot::factory()->for(Stand::factory()->inactive()))->for(User::factory())->create();
    });

    test('distinct volunteers and hours on duty are counted', function () {
        $stats = new PlanningStats;

        expect($stats->volunteers())->toBe(3)
            ->and($stats->guests())->toBe(1)
            ->and($stats->minutes())->toBe(510)
            ->and($stats->hoursLabel())->toBe('8 h 30');
    });

    test('whole hours are shown without minutes', function () {
        Registration::query()->whereNotNull('guest_name')->delete();

        expect((new PlanningStats)->hoursLabel())->toBe('5 h');
    });

    test('admins see the figures on the dashboard', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Le planning en chiffres')
            ->assertSee('8 h 30')
            ->assertSee('dont 1 personne sans compte');
    });

    test('other users do not see the figures', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Le planning en chiffres');
    });
});

test('the dashboard links to the concert', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('dashboard.modules.concert.title'))
        ->assertSee(route('concert.index'));
});
