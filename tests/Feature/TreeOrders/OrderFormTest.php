<?php

use App\Enums\TreePaymentMethod;
use App\Livewire\TreeOrders\OrderForm;
use App\Models\TreeOrder;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    Cache::flush();

    config([
        'marche.timezone' => 'Europe/Paris',
        'trees.deadline' => '2026-11-29',
        'trees.max_quantity' => 3,
        'trees.sizes' => [
            ['key' => 'lutins', 'name' => 'Le sapin des lutins', 'height' => '80/100 cm', 'price' => 27],
            ['key' => 'moyen', 'name' => 'Le sapin moyen', 'height' => '125/150 cm', 'price' => 35],
        ],
        'trees.helloasso' => ['client_id' => 'id', 'client_secret' => 'secret', 'organization_slug' => 'ecole', 'sandbox' => true],
    ]);
    $this->travelTo('2026-11-20 10:00:00');

    $this->user = User::factory()->create(['phone' => '06 11 22 33 44']);
    $this->actingAs($this->user);
});

test('guests are sent to the login page', function () {
    auth()->logout();

    $this->get(route('trees.index'))->assertRedirect(route('login'));
});

test('the page lists the sizes with their price', function () {
    $this->get(route('trees.index'))
        ->assertOk()
        ->assertSee('Le sapin des lutins')
        ->assertSee('80/100 cm')
        ->assertSee("27\u{00A0}€", false)
        ->assertSee("35\u{00A0}€", false);
});

test('quantities stay between zero and the maximum and the total follows', function () {
    Livewire::test(OrderForm::class)
        ->call('decrement', 'lutins')
        ->assertSet('quantities.lutins', 0)
        ->call('increment', 'lutins')
        ->call('increment', 'lutins')
        ->call('increment', 'moyen')
        ->assertSet('quantities.lutins', 2)
        ->assertSee("89\u{00A0}€", false)
        ->call('increment', 'moyen')
        ->call('increment', 'moyen')
        ->call('increment', 'moyen')
        ->assertSet('quantities.moyen', 3);
});

test('an order by cheque is placed and listed', function () {
    Livewire::test(OrderForm::class)
        ->set('quantities.moyen', 2)
        ->call('orderByCheque')
        ->assertHasNoErrors()
        ->assertSet('quantities.moyen', 0);

    $order = TreeOrder::query()->sole();

    expect($order->user_id)->toBe($this->user->id)
        ->and($order->customer_phone)->toBe('06 11 22 33 44')
        ->and($order->total_cents)->toBe(7000);

    Livewire::test(OrderForm::class)
        ->assertSee($order->number)
        ->assertSee(__('trees.status.pending'))
        ->assertSee(route('trees.documents.order-form', $order));
});

test('an order needs a tree and a phone number', function () {
    $this->user->update(['phone' => null]);

    Livewire::test(OrderForm::class)
        ->call('orderByCheque')
        ->assertHasErrors(['quantities', 'phone']);

    expect(TreeOrder::count())->toBe(0);
});

test('a phone typed in the form is kept on the order only', function () {
    $this->user->update(['phone' => null]);

    Livewire::test(OrderForm::class)
        ->set('quantities.lutins', 1)
        ->set('phone', '07 00 00 00 00')
        ->call('orderByCheque')
        ->assertHasNoErrors();

    expect(TreeOrder::query()->sole()->customer_phone)->toBe('07 00 00 00 00')
        ->and($this->user->fresh()->phone)->toBeNull();
});

test('ordering online sends the family to HelloAsso', function () {
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/checkout-intents' => Http::response(['id' => 654, 'redirectUrl' => 'https://pay.example/654']),
    ]);

    Livewire::test(OrderForm::class)
        ->set('quantities.lutins', 1)
        ->call('orderAndPay')
        ->assertRedirect('https://pay.example/654');

    expect(TreeOrder::query()->sole()->helloasso_checkout_id)->toBe(654);
});

test('when HelloAsso is down the order stays pending with a message', function () {
    Http::fake(['*' => Http::response([], 503)]);

    Livewire::test(OrderForm::class)
        ->set('quantities.lutins', 1)
        ->call('orderAndPay')
        ->assertNoRedirect();

    expect(TreeOrder::count())->toBe(1);
});

test('without HelloAsso credentials only the cheque is offered', function () {
    config(['trees.helloasso.client_id' => null]);

    Livewire::test(OrderForm::class)
        ->assertDontSee(__('trees.form.pay_online'))
        ->assertSee(__('trees.form.pay_by_cheque'))
        ->set('quantities.lutins', 1)
        ->call('orderAndPay');

    expect(TreeOrder::count())->toBe(0);
});

test('a pending order can be paid again or cancelled', function () {
    $order = TreeOrder::factory()->for($this->user)->withLine('lutins', 1, 2700)->create();

    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/checkout-intents' => Http::response(['id' => 1, 'redirectUrl' => 'https://pay.example/1']),
    ]);

    Livewire::test(OrderForm::class)->call('pay', $order->id)->assertRedirect('https://pay.example/1');

    Livewire::test(OrderForm::class)->call('cancel', $order->id);
    expect($order->fresh()->isCancelled())->toBeTrue();
});

test('a family cannot act on the order of another family', function (string $action) {
    $order = TreeOrder::factory()->withLine('lutins', 1, 2700)->create();

    Livewire::test(OrderForm::class)->call($action, $order->id)->assertForbidden();

    expect($order->fresh()->isCancelled())->toBeFalse();
})->with(['pay', 'cancel']);

test('the orders of other families are not listed', function () {
    $other = TreeOrder::factory()->create();

    Livewire::test(OrderForm::class)->assertDontSee($other->number);
});

test('a paid order shows its pickup slip', function () {
    $order = TreeOrder::factory()->for($this->user)->paid(TreePaymentMethod::HelloAsso)->withLine('lutins', 1, 2700)->create();

    Livewire::test(OrderForm::class)
        ->assertSee(__('trees.status.paid'))
        ->assertSee(route('trees.documents.pickup-slip', $order))
        ->assertDontSee(__('trees.mine.cancel'));
});

test('orders are closed after the deadline', function () {
    $this->travelTo('2026-11-30 10:00:00');

    Livewire::test(OrderForm::class)
        ->assertSee(__('trees.closed'))
        ->assertDontSee(__('trees.form.pay_by_cheque'))
        ->set('quantities.lutins', 1)
        ->call('orderByCheque');

    expect(TreeOrder::count())->toBe(0);
});

test('the payment result is shown after coming back from HelloAsso', function () {
    $this->withSession(['tree_status' => 'paid', 'tree_number' => 'S-0042'])
        ->get(route('trees.index'))
        ->assertSee(__('trees.toasts.paid', ['number' => 'S-0042']));
});

test('the menu and the home page link to the tree orders', function () {
    $this->get(route('dashboard'))
        ->assertSee(route('trees.index'))
        ->assertSee(__('navigation.trees'));
});
