<?php

use App\Enums\TreePaymentMethod;
use App\Exceptions\TreeOrdersClosed;
use App\Mail\TreeOrderPaid;
use App\Models\TreeOrder;
use App\Models\User;
use App\TreeOrders\HelloAsso\HelloAssoCheckout;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    Cache::flush();

    config([
        'marche.timezone' => 'Europe/Paris',
        'trees.deadline' => '2026-11-29',
        'trees.helloasso' => ['client_id' => 'id', 'client_secret' => 'secret', 'organization_slug' => 'ecole', 'sandbox' => true],
    ]);
    $this->travelTo('2026-11-20 10:00:00');

    $this->user = User::factory()->create();
    $this->order = TreeOrder::factory()->for($this->user)->withLine('moyen', 2, 3500)->create(['helloasso_checkout_id' => 321]);
});

/**
 * @param  array<string, mixed>|null  $order  "order" part of the checkout intent, null while unpaid
 */
function fakeCheckout(?array $order, ?int $treeOrderId = null): void
{
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/checkout-intents/321' => Http::response(array_filter([
            'id' => 321,
            'metadata' => ['tree_order_id' => $treeOrderId ?? TreeOrder::query()->value('id')],
            'order' => $order,
        ])),
        '*/checkout-intents' => Http::response(['id' => 654, 'redirectUrl' => 'https://pay.example/654']),
    ]);
}

function paidCheckout(int $total = 7000, string $state = 'Authorized'): array
{
    return ['id' => 777, 'amount' => ['total' => $total], 'payments' => [['id' => 1, 'amount' => $total, 'state' => $state]]];
}

test('starting a payment stores the checkout and returns the HelloAsso page', function () {
    fakeCheckout(null);

    $url = app(HelloAssoCheckout::class)->start($this->order);

    expect($url)->toBe('https://pay.example/654')
        ->and($this->order->fresh()->helloasso_checkout_id)->toBe(654);
});

test('paid, cancelled or late orders cannot be paid online', function (Closure $prepare, string $exception) {
    fakeCheckout(null);
    $prepare($this->order);

    expect(fn () => app(HelloAssoCheckout::class)->start($this->order->fresh()))->toThrow($exception);
})->with([
    'paid' => [fn (TreeOrder $order) => $order->forceFill(['paid_at' => now(), 'payment_method' => TreePaymentMethod::Cheque])->save(), InvalidArgumentException::class],
    'cancelled' => [fn (TreeOrder $order) => $order->forceFill(['cancelled_at' => now()])->save(), InvalidArgumentException::class],
    'late' => [fn () => test()->travelTo('2026-11-30 10:00:00'), TreeOrdersClosed::class],
]);

test('the browser return confirms a valid payment', function () {
    fakeCheckout(paidCheckout());

    $this->actingAs($this->user)
        ->get(route('trees.payment.return', $this->order))
        ->assertRedirect(route('trees.index'))
        ->assertSessionHas('tree_status', 'paid');

    $order = $this->order->fresh();
    expect($order->isPaid())->toBeTrue()
        ->and($order->payment_method)->toBe(TreePaymentMethod::HelloAsso)
        ->and($order->helloasso_order_id)->toBe(777);

    Mail::assertSent(TreeOrderPaid::class);
});

test('a payment with the wrong amount or not authorized leaves the order pending', function (array $checkoutOrder) {
    fakeCheckout($checkoutOrder);

    $this->actingAs($this->user)
        ->get(route('trees.payment.return', $this->order))
        ->assertSessionHas('tree_status', 'pending');

    expect($this->order->fresh()->isPaid())->toBeFalse();
    Mail::assertNothingSent();
})->with([
    'wrong amount' => [paidCheckout(100)],
    'refused' => [paidCheckout(7000, 'Refused')],
]);

test('an unpaid checkout leaves the order pending', function () {
    fakeCheckout(null);

    $this->actingAs($this->user)->get(route('trees.payment.return', $this->order));

    expect($this->order->fresh()->isPaid())->toBeFalse();
});

test('a checkout of another order is never accepted', function () {
    fakeCheckout(paidCheckout(), treeOrderId: 999);

    $this->actingAs($this->user)->get(route('trees.payment.return', $this->order));

    expect($this->order->fresh()->isPaid())->toBeFalse();
});

test('another family cannot use the return url', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('trees.payment.return', $this->order))
        ->assertForbidden();
});

test('an aborted payment goes back to the order page', function (string $route) {
    $this->actingAs($this->user)
        ->get(route($route, $this->order))
        ->assertRedirect(route('trees.index'))
        ->assertSessionHas('tree_status', 'aborted');
})->with(['trees.payment.back', 'trees.payment.error']);

test('the notification confirms the payment once, whatever the number of calls', function () {
    fakeCheckout(paidCheckout());

    $payload = ['eventType' => 'Order', 'data' => ['id' => 777], 'metadata' => ['tree_order_id' => $this->order->id]];

    $this->postJson(route('trees.payment.notification'), $payload)->assertOk();
    $this->postJson(route('trees.payment.notification'), $payload)->assertOk();
    $this->actingAs($this->user)->get(route('trees.payment.return', $this->order));

    expect($this->order->fresh()->isPaid())->toBeTrue();
    Mail::assertSentCount(1);
});

test('the notification is also received at the address declared to HelloAsso', function () {
    fakeCheckout(paidCheckout());

    $this->postJson('/helloasso', ['eventType' => 'Order', 'data' => ['id' => 777], 'metadata' => ['tree_order_id' => $this->order->id]])
        ->assertOk();

    expect($this->order->fresh()->isPaid())->toBeTrue();
});

test('a notification never trusts its own content', function () {
    fakeCheckout(null);

    $this->postJson(route('trees.payment.notification'), [
        'eventType' => 'Order',
        'data' => ['amount' => ['total' => 7000], 'payments' => [['state' => 'Authorized']]],
        'metadata' => ['tree_order_id' => $this->order->id],
    ])->assertOk();

    expect($this->order->fresh()->isPaid())->toBeFalse();
});

test('a notification for an unknown order is ignored', function () {
    Http::fake();

    $this->postJson(route('trees.payment.notification'), ['eventType' => 'Payment', 'metadata' => ['tree_order_id' => 999]])->assertOk();
    $this->postJson(route('trees.payment.notification'), ['eventType' => 'Form'])->assertOk();

    Http::assertNothingSent();
});

test('HelloAsso being down makes the notification fail so that it is sent again', function () {
    Http::fake(['*' => Http::response([], 503)]);

    $this->postJson(route('trees.payment.notification'), ['eventType' => 'Order', 'metadata' => ['tree_order_id' => $this->order->id]])
        ->assertStatus(503);
});

test('the public notification url is rate limited', function () {
    Http::fake();

    foreach (range(1, 60) as $call) {
        $this->postJson(route('trees.payment.notification'), ['eventType' => 'Form']);
    }

    $this->postJson(route('trees.payment.notification'), ['eventType' => 'Form'])->assertTooManyRequests();
});

test('a notification of the concert ticketing refreshes the tickets', function () {
    config(['concert.form_slug' => 'orchestre']);
    Cache::put('concert.tickets', ['fetched_at' => now()->getTimestamp(), 'items' => []]);

    $this->postJson('/helloasso', ['eventType' => 'Order', 'data' => ['formType' => 'Event', 'formSlug' => 'orchestre']])->assertOk();

    expect(Cache::has('concert.tickets'))->toBeFalse()
        ->and($this->order->fresh()->isPaid())->toBeFalse();
});

test('a notification of another event is ignored', function () {
    config(['concert.form_slug' => 'orchestre']);
    Cache::put('concert.tickets', ['fetched_at' => now()->getTimestamp(), 'items' => []]);

    $this->postJson('/helloasso', ['eventType' => 'Order', 'data' => ['formType' => 'Event', 'formSlug' => 'autre']])->assertOk();

    expect(Cache::has('concert.tickets'))->toBeTrue();
    Http::assertNothingSent();
});
