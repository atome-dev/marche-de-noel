<?php

use App\Enums\StandType;
use App\Livewire\Cookies\Index;
use App\Livewire\Cookies\Show;
use App\Models\Commitment;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\SchoolClass;
use App\Models\Stand;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'marche.orders.ingredients' => ['Poudre d\'amande', 'Poudre de noisette', 'Noix de coco râpée'],
        'marche.orders.quantities' => [1, 2, 3],
        'marche.orders.deadline' => '2026-11-15 23:59',
    ]);
    $this->travelTo('2026-11-01 10:00');

    $this->class = SchoolClass::query()->ordered()->first();
    $this->user = User::factory()->create();
    $this->user->schoolClasses()->attach($this->class);
    $this->actingAs($this->user);
});

describe('recipes list', function () {
    test('guests are redirected to login', function () {
        auth()->logout();

        $this->get(route('cookies.index'))->assertRedirect(route('login'));
    });

    test('recipes of my classes come first, then the others', function () {
        Recipe::factory()->create(['title' => 'Sablés de ma classe'])->schoolClasses()->attach($this->class);
        Recipe::factory()->create(['title' => 'Étoiles à la cannelle']);

        $this->get(route('cookies.index'))
            ->assertOk()
            ->assertSeeLivewire(Index::class)
            ->assertSeeInOrder([
                __('cookies.index.mine'),
                'Sablés de ma classe',
                __('cookies.index.others'),
                'Étoiles à la cannelle',
            ]);
    });

    test('every recipe is listed in one block when none matches my classes', function () {
        Recipe::factory()->create(['title' => 'Étoiles à la cannelle']);

        Livewire::test(Index::class)
            ->assertSee('Étoiles à la cannelle')
            ->assertSee(__('cookies.index.all'))
            ->assertDontSee(__('cookies.index.mine'))
            ->assertDontSee(__('cookies.index.others'));
    });

    describe('announced kilos', function () {
        beforeEach(function () {
            $this->recipe = Recipe::factory()->create(['title' => 'Étoiles à la cannelle']);
            Commitment::factory()->for($this->recipe)->create(['quantity_kg' => 2]);
            Commitment::factory()->for($this->recipe)->create(['quantity_kg' => 3]);

            $this->manager = User::factory()->create();
            Stand::factory()->ofType(StandType::Cookies)->create()->managers()->attach($this->manager);
        });

        test('managers of the cookies stand see the total announced for each recipe', function () {
            Recipe::factory()->create(['title' => 'Sablés délaissés']);

            Livewire::actingAs($this->manager)->test(Index::class)
                ->assertSeeInOrder([
                    'Étoiles à la cannelle',
                    trans_choice('cookies.index.total_kg', 5),
                    'Sablés délaissés',
                    trans_choice('cookies.index.total_kg', 0),
                ]);
        });

        test('admins see the total too', function () {
            Livewire::actingAs(User::factory()->admin()->create())->test(Index::class)
                ->assertSee(trans_choice('cookies.index.total_kg', 5));
        });

        test('volunteers do not see any total', function () {
            Livewire::test(Index::class)
                ->assertSee('Étoiles à la cannelle')
                ->assertDontSee('annoncé');
        });
    });
});

describe('recipe page', function () {
    beforeEach(function () {
        $this->recipe = Recipe::factory()->create([
            'title' => 'Croissants à la vanille',
            'instructions' => '<p>Mélanger <strong>délicatement</strong>.</p><script>alert(1)</script>',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'pdf_url' => 'https://example.org/recette.pdf',
        ]);
        RecipeIngredient::factory()->for($this->recipe)->create(['name' => 'Farine', 'quantity' => '500', 'unit' => 'g']);
        RecipeIngredient::factory()->for($this->recipe)->orderable('poudre-damande', 200)
            ->create(['name' => 'Poudre d\'amande', 'quantity' => '200', 'unit' => 'g']);
    });

    test('shows ingredients, safe instructions, video and pdf', function () {
        $this->get(route('cookies.show', $this->recipe))
            ->assertOk()
            ->assertSee('Croissants à la vanille')
            ->assertSee('Farine')
            ->assertSee('500 g')
            ->assertSee('<strong>délicatement</strong>', escape: false)
            ->assertDontSee('alert(1)')
            ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
            ->assertSee('https://example.org/recette.pdf');
    });

    test('the quantity defaults to 1 kg and can be validated', function () {
        Livewire::test(Show::class, ['recipe' => $this->recipe])
            ->assertSet('quantity', 1)
            ->set('quantity', 2)
            ->call('saveCommitment')
            ->assertHasNoErrors();

        expect(Commitment::sole()->quantity_kg)->toBe(2);
    });

    test('order buttons only appear after validation, for orderable ingredients of the recipe', function () {
        $component = Livewire::test(Show::class, ['recipe' => $this->recipe])
            ->assertDontSee(__('cookies.show.order', ['ingredient' => 'la poudre d\'amande']));

        $component->call('saveCommitment')
            ->assertSee('Commander')
            ->assertSee('Poudre d\'amande')
            ->assertDontSee('Poudre de noisette');
    });

    test('an ingredient is ordered in the recipe proportion and can be cancelled', function () {
        $component = Livewire::test(Show::class, ['recipe' => $this->recipe])
            ->set('quantity', 3)
            ->call('saveCommitment')
            ->call('order', 'poudre-damande')
            ->assertHasNoErrors()
            ->assertSee('600 g');

        $this->assertDatabaseHas('ingredient_orders', ['orderable_key' => 'poudre-damande', 'grams' => 600]);

        $component->call('cancelOrder', 'poudre-damande');

        $this->assertDatabaseCount('ingredient_orders', 0);
    });

    test('changing the quantity cancels the orders', function () {
        Livewire::test(Show::class, ['recipe' => $this->recipe])
            ->call('saveCommitment')
            ->call('order', 'poudre-damande')
            ->set('quantity', 2)
            ->call('saveCommitment')
            ->assertSee(__('cookies.show.orders_cancelled'));

        $this->assertDatabaseCount('ingredient_orders', 0);
        expect(Commitment::sole()->quantity_kg)->toBe(2);
    });

    test('the commitment can be withdrawn', function () {
        Livewire::test(Show::class, ['recipe' => $this->recipe])
            ->call('saveCommitment')
            ->call('withdraw');

        expect(Commitment::count())->toBe(0);
    });

    test('orders are locked after the deadline', function () {
        $component = Livewire::test(Show::class, ['recipe' => $this->recipe])
            ->call('saveCommitment')
            ->call('order', 'poudre-damande');

        $this->travelTo('2026-11-16 08:00');

        $component->call('cancelOrder', 'poudre-damande')
            ->assertHasErrors(['orders'])
            ->assertSee(__('cookies.orders.locked'));

        $this->assertDatabaseCount('ingredient_orders', 1);
    });

    test('an unknown ingredient cannot be ordered', function () {
        Livewire::test(Show::class, ['recipe' => $this->recipe])
            ->call('saveCommitment')
            ->call('order', 'poudre-de-noisette')
            ->assertNotFound();
    });

    test('ordering requires a validated quantity', function () {
        Livewire::test(Show::class, ['recipe' => $this->recipe])
            ->call('order', 'poudre-damande')
            ->assertNotFound();
    });
});
