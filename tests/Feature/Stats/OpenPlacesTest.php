<?php

use App\Livewire\Stats\Index;
use App\Models\Registration;
use App\Models\Slot;
use App\Models\Stand;
use App\Models\User;
use App\Planning\OpenPlacesSummary;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo('2026-11-22 10:00:00');

    config(['marche.dates' => ['2026-11-28', '2026-11-29']]);

    $this->buvette = Stand::factory()->create(['name' => 'Buvette', 'position' => 1]);
    $morning = Slot::factory()->for($this->buvette)->at('2026-11-28', '10:00', '12:00')->create(['capacity' => 3]);
    Registration::factory()->for($morning)->create();
    $full = Slot::factory()->for($this->buvette)->at('2026-11-28', '14:00', '16:00')->create(['capacity' => 1]);
    Registration::factory()->for($full)->count(2)->create(); // overbooked: never negative
    Slot::factory()->for($this->buvette)->at('2026-11-29', '10:00', '12:00')->create(['capacity' => 2]);
    Slot::factory()->for($this->buvette)->at('2026-11-20', '10:00', '12:00')->create(['capacity' => 5]); // past

    $this->crepes = Stand::factory()->create(['name' => 'Crêpes', 'position' => 2]);
    Slot::factory()->for($this->crepes)->at('2026-11-25', '10:00', '12:00')->create(['capacity' => 4]); // preparation

    $inactive = Stand::factory()->create(['name' => 'Fermé', 'is_active' => false]);
    Slot::factory()->for($inactive)->at('2026-11-28', '10:00', '12:00')->create();
});

test('places to fill are counted by stand and by day, from today on', function () {
    $summary = new OpenPlacesSummary;

    expect($summary->days())->toBe(['2026-11-25', '2026-11-28', '2026-11-29'])
        ->and($summary->isPreparation('2026-11-25'))->toBeTrue()
        ->and($summary->isPreparation('2026-11-28'))->toBeFalse()
        ->and(array_map(fn (array $row) => [$row['stand']->name, $row['cells'], $row['open'], $row['capacity']], $summary->rows()))->toBe([
            ['Buvette', [
                '2026-11-25' => null,
                '2026-11-28' => ['open' => 2, 'capacity' => 4],
                '2026-11-29' => ['open' => 2, 'capacity' => 2],
            ], 4, 6],
            ['Crêpes', [
                '2026-11-25' => ['open' => 4, 'capacity' => 4],
                '2026-11-28' => null,
                '2026-11-29' => null,
            ], 4, 4],
        ])
        ->and($summary->dayTotal('2026-11-28'))->toBe(['open' => 2, 'capacity' => 4])
        ->and($summary->total())->toBe(['open' => 8, 'capacity' => 10]);
});

test('the summary can be limited to some stands', function () {
    $summary = new OpenPlacesSummary([$this->crepes->id]);

    expect(array_map(fn (array $row) => $row['stand']->name, $summary->rows()))->toBe(['Crêpes'])
        ->and($summary->days())->toBe(['2026-11-25']);
});

test('administrators see every stand', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('stats'))
        ->assertOk()
        ->assertSee('Places à pourvoir')
        ->assertSee('Buvette')
        ->assertSee('Crêpes')
        ->assertDontSee('Fermé')
        ->assertSee('8 / 10');
});

test('managers only see their own stands', function () {
    $manager = User::factory()->create();
    $this->crepes->managers()->attach($manager);

    $this->actingAs($manager);

    Livewire::test(Index::class)
        ->assertSee('Crêpes')
        ->assertDontSee('Buvette')
        ->assertSee('4 / 4');

    $this->get(route('dashboard'))->assertSee(route('stats'));
});

test('volunteers cannot open the statistics', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('stats'))->assertForbidden();
    $this->get(route('dashboard'))->assertDontSee(route('stats'));
});

test('guests are sent to the login page', function () {
    $this->get(route('stats'))->assertRedirect(route('login'));
});
