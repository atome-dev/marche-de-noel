<?php

use App\Cookies\RecipeCommitmentsSummary;
use App\Enums\StandType;
use App\Livewire\Stats\Index;
use App\Models\Commitment;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\Stand;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $easy = RecipeCategory::factory()->create(['name' => 'Recette facile', 'position' => 1]);
    $elaborate = RecipeCategory::factory()->create(['name' => 'Recette élaborée', 'position' => 2]);

    $this->sables = Recipe::factory()->for($easy, 'category')->create(['title' => 'Sablés']);
    $this->macarons = Recipe::factory()->for($elaborate, 'category')->create(['title' => 'Macarons']);
    $this->etoiles = Recipe::factory()->for($easy, 'category')->create(['title' => 'Étoiles à la cannelle']);

    $camille = User::factory()->create();
    Commitment::factory()->for($camille)->for($this->sables)->create(['quantity_kg' => 2]);
    Commitment::factory()->for($camille)->for($this->macarons)->create(['quantity_kg' => 1]);
    Commitment::factory()->for(User::factory())->for($this->sables)->create(['quantity_kg' => 3]);
});

test('kilos and families are counted by recipe, grouped by biscuit family', function () {
    $summary = new RecipeCommitmentsSummary;

    expect($summary->groups()->map(fn (array $rows) => array_map(fn (array $row) => [$row['recipe']->title, $row['families'], $row['kilos']], $rows))->all())->toBe([
        'Recette facile' => [['Étoiles à la cannelle', 0, 0], ['Sablés', 2, 5]],
        'Recette élaborée' => [['Macarons', 1, 1]],
    ])
        ->and($summary->kilos())->toBe(6)
        ->and($summary->families())->toBe(2)
        ->and($summary->recipesWithoutCommitment())->toBe(1);
});

test('cookies managers see the cookies tab', function () {
    $manager = User::factory()->create();
    Stand::factory()->ofType(StandType::Cookies)->create()->managers()->attach($manager);

    $this->actingAs($manager);

    Livewire::test(Index::class)
        ->assertSee('Petits gâteaux')
        ->call('selectTab', Index::TAB_COOKIES)
        ->assertSet('tab', Index::TAB_COOKIES)
        ->assertSee('Kilos prévus par recette')
        ->assertSee('Macarons')
        ->assertSee('6 kg');

    $this->get(route('stats', ['onglet' => 'petits-gateaux']))
        ->assertOk()
        ->assertSee('Étoiles à la cannelle');
});

test('administrators see the cookies tab', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::withQueryParams(['onglet' => 'petits-gateaux'])
        ->test(Index::class)
        ->assertSet('tab', Index::TAB_COOKIES)
        ->assertSee('Sablés');
});

test('other managers only get the planning tab', function () {
    $manager = User::factory()->create();
    Stand::factory()->create()->managers()->attach($manager);

    $this->actingAs($manager);

    Livewire::withQueryParams(['onglet' => 'petits-gateaux'])
        ->test(Index::class)
        ->assertSet('tab', Index::TAB_PLANNING)
        ->assertDontSee('Petits gâteaux')
        ->assertDontSee('Sablés')
        ->call('selectTab', Index::TAB_COOKIES)
        ->assertSet('tab', Index::TAB_PLANNING)
        ->assertDontSee('Sablés');
});
