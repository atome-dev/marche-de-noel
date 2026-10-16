<?php

use App\Enums\TreePaymentMethod;
use App\Livewire\TreeOrders\OrderForm;
use App\Mail\TreeOrderPlaced;
use App\Models\TreeOrder;
use App\Models\User;
use App\TreeOrders\HelloAsso\HelloAssoShop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

const SHOP_URL = 'https://www.helloasso.com/associations/ecole/boutiques/sapins';

beforeEach(function () {
    Mail::fake();
    Http::preventStrayRequests();

    config([
        'marche.timezone' => 'Europe/Paris',
        'trees.deadline' => '2026-11-29',
        'trees.sizes' => [
            ['key' => 'lutins', 'name' => 'Le sapin des lutins', 'height' => '80/100 cm', 'price' => 27],
            ['key' => 'grand', 'name' => 'Le grand sapin', 'height' => '150/175 cm', 'price' => 45],
        ],
        'trees.helloasso' => ['client_id' => null, 'client_secret' => null, 'organization_slug' => null, 'sandbox' => true, 'shop_url' => SHOP_URL],
    ]);
    $this->travelTo('2026-11-20 10:00:00');

    $this->user = User::factory()->create(['first_name' => 'Camille', 'last_name' => 'Martin', 'phone' => '06 11 22 33 44']);
    $this->actingAs($this->user);
});

test('the shop is used only without API credentials', function () {
    expect(app(HelloAssoShop::class)->url())->toBe(SHOP_URL);

    config(['trees.helloasso.client_id' => 'id', 'trees.helloasso.client_secret' => 'secret', 'trees.helloasso.organization_slug' => 'ecole']);
    expect(app(HelloAssoShop::class)->url())->toBeNull();

    config(['trees.helloasso.client_id' => null, 'trees.helloasso.shop_url' => '']);
    expect(app(HelloAssoShop::class)->url())->toBeNull();
});

test('ordering online records the order and shows what to pay in the shop', function () {
    Livewire::test(OrderForm::class)
        ->assertSee(__('trees.form.pay_on_shop'))
        ->set('quantities.lutins', 1)
        ->set('quantities.grand', 2)
        ->call('orderAndPay')
        ->assertNoRedirect()
        ->assertSet('shopOrderId', fn (int $id) => TreeOrder::query()->sole()->id === $id)
        ->assertSee(SHOP_URL)
        ->assertSee(__('trees.shop.pay_as', ['name' => 'Camille Martin']))
        ->assertSee("117\u{00A0}€", false);

    expect(TreeOrder::query()->sole())
        ->isPaid()->toBeFalse()
        ->helloasso_checkout_id->toBeNull();
});

test('a pending order shows the shop again', function () {
    $order = TreeOrder::factory()->for($this->user)->withLine('lutins', 1, 2700)->create();

    Livewire::test(OrderForm::class)
        ->call('pay', $order->id)
        ->assertNoRedirect()
        ->assertSet('shopOrderId', $order->id)
        ->assertSee(SHOP_URL);
});

test('a paid order is not shown for payment', function () {
    $order = TreeOrder::factory()->for($this->user)->paid(TreePaymentMethod::Cheque)->withLine('lutins', 1, 2700)->create();

    Livewire::test(OrderForm::class)
        ->call('pay', $order->id)
        ->assertSet('shopOrderId', null);
});

test('a family cannot open the shop for the order of another family', function () {
    $order = TreeOrder::factory()->withLine('lutins', 1, 2700)->create();

    Livewire::test(OrderForm::class)->call('pay', $order->id)->assertForbidden();
});

test('the shop is not offered after the deadline', function () {
    $order = TreeOrder::factory()->for($this->user)->withLine('lutins', 1, 2700)->create();
    $this->travelTo('2026-11-30 10:00:00');

    Livewire::test(OrderForm::class)
        ->assertDontSee(__('trees.mine.pay'))
        ->call('pay', $order->id)
        ->assertSet('shopOrderId', null);
});

test('the summary mail links to the shop', function () {
    $order = TreeOrder::factory()->for($this->user)->withLine('lutins', 1, 2700)->create();

    (new TreeOrderPlaced($order))
        ->assertSeeInHtml(SHOP_URL)
        ->assertSeeInHtml(__('trees.mail.placed.shop_action'));
});

test('the summary mail of a guest order does not link to the shop', function () {
    $order = TreeOrder::factory()->guest()->withLine('lutins', 1, 2700)->create();

    (new TreeOrderPlaced($order))->assertDontSeeInHtml(SHOP_URL);
});
