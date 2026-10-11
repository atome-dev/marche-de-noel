<?php

use App\Cookies\CookieOrdering;
use App\Cookies\OrdersSummary;
use App\Exceptions\OrdersLocked;
use App\Models\Commitment;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;

beforeEach(function () {
    config([
        'marche.orders.ingredients' => ['Poudre d\'amande', 'Poudre de noisette'],
        'marche.orders.quantities' => [1, 2, 3],
        'marche.orders.deadline' => '2026-11-15 23:59',
    ]);
    $this->travelTo('2026-11-01 10:00');

    $this->ordering = app(CookieOrdering::class);
    $this->user = User::factory()->create();
    $this->recipe = Recipe::factory()->create();
    RecipeIngredient::factory()->for($this->recipe)->orderable('poudre-damande', 200)->create(['name' => 'Poudre d\'amande']);
    RecipeIngredient::factory()->for($this->recipe)->create(['name' => 'Farine']);
});

describe('commitments', function () {
    test('a family commits to bake a quantity', function () {
        [$commitment, $cancelled] = $this->ordering->commit($this->user, $this->recipe, 2);

        expect($commitment->quantity_kg)->toBe(2)
            ->and($cancelled)->toBe(0)
            ->and(Commitment::count())->toBe(1);
    });

    test('only the configured quantities are accepted', function () {
        $this->ordering->commit($this->user, $this->recipe, 5);
    })->throws(InvalidArgumentException::class);

    test('changing the quantity cancels the linked orders', function () {
        [$commitment] = $this->ordering->commit($this->user, $this->recipe, 1);
        $this->ordering->order($commitment, 'poudre-damande');

        [$commitment, $cancelled] = $this->ordering->commit($this->user, $this->recipe, 3);

        expect($cancelled)->toBe(1)
            ->and($commitment->quantity_kg)->toBe(3)
            ->and($commitment->orders()->count())->toBe(0);
    });

    test('confirming the same quantity keeps the orders', function () {
        [$commitment] = $this->ordering->commit($this->user, $this->recipe, 2);
        $this->ordering->order($commitment, 'poudre-damande');

        [, $cancelled] = $this->ordering->commit($this->user, $this->recipe, 2);

        expect($cancelled)->toBe(0)
            ->and($commitment->orders()->count())->toBe(1);
    });

    test('a commitment can be withdrawn with its orders', function () {
        [$commitment] = $this->ordering->commit($this->user, $this->recipe, 1);
        $this->ordering->order($commitment, 'poudre-damande');

        $this->ordering->withdraw($this->user, $this->recipe);

        expect(Commitment::count())->toBe(0);
        $this->assertDatabaseCount('ingredient_orders', 0);
    });
});

describe('orders', function () {
    test('the ordered weight follows the recipe proportion', function () {
        [$commitment] = $this->ordering->commit($this->user, $this->recipe, 3);

        $order = $this->ordering->order($commitment, 'poudre-damande');

        expect($order->grams)->toBe(600);
    });

    test('an ingredient that is not orderable in the recipe is refused', function () {
        [$commitment] = $this->ordering->commit($this->user, $this->recipe, 1);

        $this->ordering->order($commitment, 'poudre-de-noisette');
    })->throws(InvalidArgumentException::class);

    test('each order can be cancelled individually', function () {
        RecipeIngredient::factory()->for($this->recipe)->orderable('poudre-de-noisette', 100)->create();
        [$commitment] = $this->ordering->commit($this->user, $this->recipe->fresh(), 1);
        $this->ordering->order($commitment, 'poudre-damande');
        $this->ordering->order($commitment, 'poudre-de-noisette');

        $this->ordering->cancelOrder($commitment, 'poudre-damande');

        expect($commitment->orders()->pluck('orderable_key')->all())->toBe(['poudre-de-noisette']);
    });
});

describe('after the deadline', function () {
    beforeEach(function () {
        [$this->commitment] = $this->ordering->commit($this->user, $this->recipe, 1);
        $this->ordering->order($this->commitment, 'poudre-damande');

        $this->travelTo('2026-11-16 00:00');
    });

    test('orders are locked', function () {
        expect($this->ordering->isLocked())->toBeTrue();
    });

    test('ordering is refused', function () {
        $this->ordering->order($this->commitment, 'poudre-damande');
    })->throws(OrdersLocked::class);

    test('cancelling an order is refused', function () {
        $this->ordering->cancelOrder($this->commitment, 'poudre-damande');
    })->throws(OrdersLocked::class);

    test('a commitment with orders cannot change', function () {
        $this->ordering->commit($this->user, $this->recipe, 2);
    })->throws(OrdersLocked::class);

    test('a commitment with orders cannot be withdrawn', function () {
        $this->ordering->withdraw($this->user, $this->recipe);
    })->throws(OrdersLocked::class);

    test('committing without ordering stays possible', function () {
        $other = Recipe::factory()->create();

        [$commitment] = $this->ordering->commit($this->user, $other, 2);
        [$commitment] = $this->ordering->commit($this->user, $other, 3);

        expect($commitment->quantity_kg)->toBe(3);
    });
});

test('orders are never locked without a deadline', function () {
    config(['marche.orders.deadline' => null]);

    expect($this->ordering->isLocked())->toBeFalse();
});

test('the summary lists families, ingredients, subtotals and totals', function () {
    $camille = User::factory()->create(['first_name' => 'Camille', 'last_name' => 'Martin']);
    $dominique = User::factory()->create(['first_name' => 'Dominique', 'last_name' => 'Arnaud']);
    User::factory()->create(['last_name' => 'Sans commande']);

    $second = Recipe::factory()->create();
    RecipeIngredient::factory()->for($second)->orderable('poudre-damande', 150)->create();
    RecipeIngredient::factory()->for($second)->orderable('poudre-de-noisette', 100)->create();

    [$a] = $this->ordering->commit($camille, $this->recipe, 2);
    $this->ordering->order($a, 'poudre-damande');
    [$b] = $this->ordering->commit($camille, $second, 1);
    $this->ordering->order($b, 'poudre-damande');
    $this->ordering->order($b, 'poudre-de-noisette');
    [$c] = $this->ordering->commit($dominique, $second, 3);
    $this->ordering->order($c, 'poudre-de-noisette');

    $summary = app(OrdersSummary::class);

    expect($summary->ingredients())->toBe(['poudre-damande' => 'Poudre d\'amande', 'poudre-de-noisette' => 'Poudre de noisette'])
        ->and($summary->rows()->map(fn (array $row) => [$row['user']->last_name, $row['grams'], $row['total']])->all())->toBe([
            ['Arnaud', ['poudre-damande' => 0, 'poudre-de-noisette' => 300], 300],
            ['Martin', ['poudre-damande' => 550, 'poudre-de-noisette' => 100], 650],
        ])
        ->and($summary->totals())->toBe(['poudre-damande' => 550, 'poudre-de-noisette' => 400])
        ->and($summary->grandTotal())->toBe(950);
});
