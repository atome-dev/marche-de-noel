<?php

use App\Enums\StandType;
use App\Enums\TreeOrderStatus;
use App\Enums\TreePaymentMethod;
use App\Livewire\TreeOrders\Manage;
use App\Mail\TreeOrderPaid;
use App\Models\Stand;
use App\Models\TreeOrder;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();

    config([
        'marche.timezone' => 'Europe/Paris',
        'trees.deadline' => '2026-11-29',
        'trees.sizes' => [
            ['key' => 'lutins', 'name' => 'Le sapin des lutins', 'height' => '80/100 cm', 'price' => 27],
            ['key' => 'moyen', 'name' => 'Le sapin moyen', 'height' => '125/150 cm', 'price' => 35],
        ],
    ]);
    $this->travelTo('2026-11-20 10:00:00');

    $this->manager = User::factory()->create();
    Stand::factory()->ofType(StandType::TreeOrder)->create()->managers()->attach($this->manager);
    $this->actingAs($this->manager);
});

test('only tree stand managers and administrators open the management pages', function (string $route) {
    $this->actingAs(User::factory()->create())->get(route($route))->assertForbidden();
    $this->actingAs($this->manager)->get(route($route))->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get(route($route))->assertOk();
})->with(['trees.manage', 'trees.summary', 'trees.pickup', 'trees.pickup.print']);

test('managers see every order with the phone number', function () {
    $order = TreeOrder::factory()->withLine('moyen', 2, 3500, 'Le sapin moyen')->create(['customer_name' => 'Camille Martin', 'customer_phone' => '06 99 88 77 66']);

    Livewire::test(Manage::class)
        ->assertSee('Camille Martin')
        ->assertSee('06 99 88 77 66')
        ->assertSee($order->number)
        ->assertSee('2 × Le sapin moyen', false);
});

test('orders are filtered by status and searched', function () {
    TreeOrder::factory()->create(['customer_name' => 'Camille Martin']);
    TreeOrder::factory()->paid()->create(['customer_name' => 'Dominique Arnaud']);

    Livewire::test(Manage::class)
        ->set('status', TreeOrderStatus::Paid->value)
        ->assertSee('Dominique Arnaud')
        ->assertDontSee('Camille Martin')
        ->set('status', '')
        ->set('search', 'camille')
        ->assertSee('Camille Martin')
        ->assertDontSee('Dominique Arnaud');
});

test('a cheque is recorded and the family is told', function () {
    $order = TreeOrder::factory()->create(['customer_email' => 'camille@example.com']);

    Livewire::test(Manage::class)->call('markCheque', $order->id);

    expect($order->fresh()->payment_method)->toBe(TreePaymentMethod::Cheque)
        ->and($order->fresh()->paid_by)->toBe($this->manager->id);
    Mail::assertSent(TreeOrderPaid::class);

    Livewire::test(Manage::class)->call('unmarkCheque', $order->id);
    expect($order->fresh()->isPaid())->toBeFalse();
});

test('a late cheque brings an expired order back', function () {
    $order = TreeOrder::factory()->create();
    $this->travelTo('2026-12-01 10:00:00');

    expect($order->status())->toBe(TreeOrderStatus::Expired);

    Livewire::test(Manage::class)->call('markCheque', $order->id);

    expect($order->fresh()->status())->toBe(TreeOrderStatus::Paid);
});

test('managers cancel any order', function () {
    $order = TreeOrder::factory()->paid(TreePaymentMethod::HelloAsso)->create();

    Livewire::test(Manage::class)->call('cancelOrder', $order->id);

    expect($order->fresh()->isCancelled())->toBeTrue();
});

test('volunteers cannot call the management actions', function (string $action) {
    $order = TreeOrder::factory()->create();
    $this->actingAs(User::factory()->create());

    Livewire::test(Manage::class)->assertForbidden();
})->with(['markCheque', 'cancelOrder']);

test('managers enter an order for someone without an account, even after the deadline', function () {
    $this->travelTo('2026-12-01 10:00:00');

    Livewire::test(Manage::class)
        ->set('guestName', 'Mamie Jeanne')
        ->set('guestPhone', '01 23 45 67 89')
        ->set('guestQuantities.lutins', 1)
        ->set('guestChequeReceived', true)
        ->call('createGuestOrder')
        ->assertHasNoErrors()
        ->assertSee('Mamie Jeanne');

    $order = TreeOrder::query()->sole();
    expect($order->user_id)->toBeNull()
        ->and($order->isPaid())->toBeTrue()
        ->and($order->total_cents)->toBe(2700);
});

test('a guest order needs a name, a phone and a tree', function () {
    Livewire::test(Manage::class)
        ->call('createGuestOrder')
        ->assertHasErrors(['guestName', 'guestPhone', 'guestQuantities']);

    expect(TreeOrder::count())->toBe(0);
});

test('the guest order form shows its total', function () {
    Livewire::test(Manage::class)
        ->assertSeeHtml('data-guest-total')
        ->set('guestQuantities.lutins', 2)
        ->set('guestQuantities.moyen', 1)
        ->assertSet('guestTotalCents', 8900)
        ->assertSee("89\u{00A0}€", false)
        ->set('guestQuantities.moyen', '')
        ->assertSet('guestTotalCents', 5400);
});
