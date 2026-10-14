<?php

use App\Enums\StandType;
use App\Livewire\TreeOrders\Pickup;
use App\Models\Stand;
use App\Models\TreeOrder;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->manager = User::factory()->create();
    Stand::factory()->ofType(StandType::TreeOrder)->create()->managers()->attach($this->manager);
    $this->actingAs($this->manager);

    $this->paid = TreeOrder::factory()->paid()->withLine('moyen', 1, 3500, 'Le sapin moyen')->create(['customer_name' => 'Camille Martin']);
    TreeOrder::factory()->paid()->pickedUp()->create(['customer_name' => 'Dominique Arnaud']);
    TreeOrder::factory()->create(['customer_name' => 'Pas Payé']);
    TreeOrder::factory()->paid()->cancelled()->create(['customer_name' => 'Annulé Avant']);
});

test('only paid orders are listed, with a counter', function () {
    Livewire::test(Pickup::class)
        ->assertSeeInOrder(['Camille Martin', 'Dominique Arnaud'])
        ->assertDontSee('Pas Payé')
        ->assertDontSee('Annulé Avant')
        ->assertSee(__('trees.pickup_list.counter', ['done' => 1, 'total' => 2]));
});

test('an order is ticked as picked up, then unticked', function () {
    Livewire::test(Pickup::class)
        ->call('togglePickup', $this->paid->id)
        ->assertSee(__('trees.pickup_list.counter', ['done' => 2, 'total' => 2]));

    expect($this->paid->fresh()->isPickedUp())->toBeTrue();

    Livewire::test(Pickup::class)->call('togglePickup', $this->paid->id);

    expect($this->paid->fresh()->isPickedUp())->toBeFalse();
});

test('an unpaid order cannot be picked up', function () {
    $unpaid = TreeOrder::query()->where('customer_name', 'Pas Payé')->sole();

    Livewire::test(Pickup::class)->call('togglePickup', $unpaid->id);

    expect($unpaid->fresh()->isPickedUp())->toBeFalse();
});

test('the list searches by name', function () {
    Livewire::test(Pickup::class)
        ->set('search', 'camille')
        ->assertSee('Camille Martin')
        ->assertDontSee('Dominique Arnaud');
});

test('the page refreshes itself', function () {
    config(['marche.planning.refresh_seconds' => 15]);
    Livewire::test(Pickup::class)->assertSeeHtml('wire:poll.15s');

    config(['marche.planning.refresh_seconds' => 0]);
    Livewire::test(Pickup::class)->assertDontSeeHtml('wire:poll');
});

test('the printable list has the paid orders', function () {
    $this->get(route('trees.pickup.print'))
        ->assertOk()
        ->assertSeeInOrder(['Camille Martin', 'Dominique Arnaud'])
        ->assertDontSee('Pas Payé')
        ->assertSee('1 × Le sapin moyen', false);
});

test('volunteers cannot tick a pickup', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Pickup::class)->assertForbidden();
});
