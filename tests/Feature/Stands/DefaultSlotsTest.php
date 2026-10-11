<?php

use App\Enums\StandType;
use App\Livewire\Stands\StandForm;
use App\Models\Slot;
use App\Models\Stand;
use App\Models\User;
use App\Planning\DefaultSlots;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'marche.dates' => ['2026-11-28', '2026-11-29'],
        'marche.planning.hour_min' => '09:00',
        'marche.planning.hour_max' => '20:00',
        'marche.planning.default_slots' => ['12:30-14:00', '14:30-16:00', '16:00-17:30', '17:30-19:00', '19:00-20:00'],
        'marche.planning.default_capacity' => 2,
    ]);
});

describe('default slots', function () {
    test('are created for every market day', function () {
        $stand = Stand::factory()->create();

        $created = app(DefaultSlots::class)->createFor($stand);

        expect($created)->toBe(10)
            ->and($stand->slots()->whereDate('date', '2026-11-28')->get()->map(fn (Slot $slot) => $slot->starts_at.'-'.$slot->ends_at)->all())
            ->toBe(['12:30-14:00', '14:30-16:00', '16:00-17:30', '17:30-19:00', '19:00-20:00'])
            ->and($stand->slots()->pluck('capacity')->unique()->all())->toBe([2]);
    });

    test('skip the ranges outside the planning hours', function () {
        config(['marche.planning.hour_max' => '19:00']);
        $stand = Stand::factory()->create();

        app(DefaultSlots::class)->createFor($stand);

        expect($stand->slots()->whereDate('date', '2026-11-28')->count())->toBe(4)
            ->and($stand->slots()->where('ends_at', '>', '19:00')->count())->toBe(0);
    });

    test('never overlap the existing slots of the stand', function () {
        $stand = Stand::factory()->create();
        Slot::factory()->for($stand)->at('2026-11-28', '13:00', '15:00')->create();

        app(DefaultSlots::class)->createFor($stand);
        app(DefaultSlots::class)->createFor($stand);

        expect($stand->slots()->whereDate('date', '2026-11-28')->get()->map(fn (Slot $slot) => $slot->starts_at.'-'.$slot->ends_at)->all())
            ->toBe(['13:00-15:00', '16:00-17:30', '17:30-19:00', '19:00-20:00'])
            ->and($stand->slots()->whereDate('date', '2026-11-29')->count())->toBe(5);
    });

    test('ignore malformed ranges', function () {
        config(['marche.planning.default_slots' => ['n-importe-quoi', '14:00-13:00', '14:30-16:00']]);
        $stand = Stand::factory()->create();

        expect(app(DefaultSlots::class)->createFor($stand))->toBe(2);
    });

    test('are added when an administrator creates a stand', function () {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(StandForm::class)
            ->set('name', 'Nouveau stand')
            ->call('save')
            ->assertHasNoErrors();

        expect(Stand::firstWhere('name', 'Nouveau stand')->slots()->count())->toBe(10);
    });
});

describe('stands import', function () {
    test('creates the reference stands with their default slots', function () {
        $this->artisan('marche:import-stands')->assertSuccessful();

        $names = require database_path('data/stands.php');

        expect(Stand::count())->toBe(count($names))
            ->and(Stand::count())->toBe(48)
            ->and(Stand::ordered()->first()->name)->toBe('Accueil & Sécurité')
            ->and(Stand::firstWhere('name', 'Petits gâteaux de Noël')->type)->toBe(StandType::Cookies)
            ->and(Stand::firstWhere('name', 'Tombola')->type)->toBe(StandType::Raffle)
            ->and(Stand::firstWhere('name', 'Crêpes')->type)->toBe(StandType::Generic)
            ->and(Stand::firstWhere('name', 'Crêpes')->slots()->count())->toBe(10);
    });

    test('can be run again without duplicating nor touching existing stands', function () {
        $existing = Stand::factory()->create(['name' => 'Crêpes', 'description' => 'Déjà saisi']);
        Slot::factory()->for($existing)->at('2026-11-28', '10:00', '12:00')->create();

        $this->artisan('marche:import-stands')->assertSuccessful();
        $this->artisan('marche:import-stands')->assertSuccessful();

        expect(Stand::count())->toBe(48)
            ->and($existing->refresh()->description)->toBe('Déjà saisi')
            ->and($existing->slots()->count())->toBe(1);
    });
});
