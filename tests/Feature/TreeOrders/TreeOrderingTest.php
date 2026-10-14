<?php

use App\Enums\TreePaymentMethod;
use App\Exceptions\TreeOrdersClosed;
use App\Mail\TreeOrderPaid;
use App\Mail\TreeOrderPlaced;
use App\Models\TreeOrder;
use App\Models\User;
use App\TreeOrders\TreeOrdering;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    config([
        'marche.timezone' => 'Europe/Paris',
        'trees.deadline' => '2026-11-29',
        'trees.max_quantity' => 10,
        'trees.sizes' => [
            ['key' => 'lutins', 'name' => 'Le sapin des lutins', 'height' => '80/100 cm', 'price' => 27],
            ['key' => 'moyen', 'name' => 'Le sapin moyen', 'height' => '125/150 cm', 'price' => 35],
        ],
    ]);
    $this->travelTo('2026-11-20 10:00:00');

    $this->ordering = app(TreeOrdering::class);
    $this->user = User::factory()->create(['first_name' => 'Camille', 'last_name' => 'Martin', 'email' => 'camille@example.com']);
    $this->manager = User::factory()->create();
});

test('a family order copies the sizes and computes the total', function () {
    $order = $this->ordering->placeForUser($this->user, ['lutins' => 2, 'moyen' => 1, 'unused' => 0], '06 11 22 33 44');

    expect($order->user_id)->toBe($this->user->id)
        ->and($order->customer_name)->toBe('Camille Martin')
        ->and($order->customer_email)->toBe('camille@example.com')
        ->and($order->customer_phone)->toBe('06 11 22 33 44')
        ->and($order->created_by)->toBe($this->user->id)
        ->and($order->total_cents)->toBe(8900)
        ->and($order->lines->map->only(['size_key', 'label', 'height', 'unit_price_cents', 'quantity'])->all())->toBe([
            ['size_key' => 'lutins', 'label' => 'Le sapin des lutins', 'height' => '80/100 cm', 'unit_price_cents' => 2700, 'quantity' => 2],
            ['size_key' => 'moyen', 'label' => 'Le sapin moyen', 'height' => '125/150 cm', 'unit_price_cents' => 3500, 'quantity' => 1],
        ]);

    Mail::assertSent(TreeOrderPlaced::class, fn (TreeOrderPlaced $mail) => $mail->hasTo('camille@example.com') && $mail->order->is($order));
});

test('invalid quantities are refused', function (array $quantities) {
    $this->ordering->placeForUser($this->user, $quantities, '06 11 22 33 44');
})->with([
    'no tree' => [['lutins' => 0]],
    'unknown size' => [['sequoia' => 1]],
    'too many' => [['lutins' => 11]],
    'negative' => [['lutins' => -1, 'moyen' => 2]],
])->throws(InvalidArgumentException::class);

test('families cannot order after the deadline', function () {
    $this->travelTo('2026-11-30 10:00:00');

    $this->ordering->placeForUser($this->user, ['lutins' => 1], '06 11 22 33 44');
})->throws(TreeOrdersClosed::class);

test('managers enter orders for people without an account, even after the deadline', function () {
    $this->travelTo('2026-11-30 10:00:00');

    $order = $this->ordering->placeForGuest($this->manager, ['moyen' => 1], 'Mamie Jeanne', '01 23 45 67 89', null, chequeReceived: true);

    expect($order->user_id)->toBeNull()
        ->and($order->created_by)->toBe($this->manager->id)
        ->and($order->isPaid())->toBeTrue()
        ->and($order->payment_method)->toBe(TreePaymentMethod::Cheque)
        ->and($order->paid_by)->toBe($this->manager->id);

    Mail::assertNothingSent();
});

test('a guest order with an email gets the order mail', function () {
    $this->ordering->placeForGuest($this->manager, ['moyen' => 1], 'Mamie Jeanne', '01 23 45 67 89', 'jeanne@example.com', chequeReceived: false);

    Mail::assertSent(TreeOrderPlaced::class, fn (TreeOrderPlaced $mail) => $mail->hasTo('jeanne@example.com'));
    Mail::assertNotSent(TreeOrderPaid::class);
});

test('families cancel a pending order before the deadline only', function () {
    $order = TreeOrder::factory()->for($this->user)->create();

    $this->ordering->cancel($order);
    expect($order->fresh()->isCancelled())->toBeTrue();

    $late = TreeOrder::factory()->for($this->user)->create();
    $this->travelTo('2026-11-30 10:00:00');

    expect(fn () => $this->ordering->cancel($late))->toThrow(TreeOrdersClosed::class);
});

test('families cannot cancel a paid order, managers can', function () {
    $order = TreeOrder::factory()->for($this->user)->paid(TreePaymentMethod::HelloAsso)->create();

    expect(fn () => $this->ordering->cancel($order))->toThrow(InvalidArgumentException::class);

    $this->ordering->cancel($order, byManager: true);
    expect($order->fresh()->isCancelled())->toBeTrue();
});

test('recording a cheque marks the order paid and sends the paid mail', function () {
    $order = TreeOrder::factory()->for($this->user)->create(['customer_email' => 'camille@example.com']);

    $this->ordering->markChequeReceived($order, $this->manager);

    $order->refresh();
    expect($order->isPaid())->toBeTrue()
        ->and($order->payment_method)->toBe(TreePaymentMethod::Cheque)
        ->and($order->paid_by)->toBe($this->manager->id);

    Mail::assertSent(TreeOrderPaid::class, fn (TreeOrderPaid $mail) => $mail->hasTo('camille@example.com'));

    $this->ordering->unmarkCheque($order);
    expect($order->fresh()->only(['paid_at', 'paid_by', 'payment_method']))
        ->toBe(['paid_at' => null, 'paid_by' => null, 'payment_method' => null]);
});

test('a cheque can be recorded after the deadline', function () {
    $order = TreeOrder::factory()->for($this->user)->create();
    $this->travelTo('2026-12-01 10:00:00');

    $this->ordering->markChequeReceived($order, $this->manager);

    expect($order->fresh()->isPaid())->toBeTrue();
});

test('a helloasso payment is recorded once and mailed once', function () {
    $order = TreeOrder::factory()->for($this->user)->create();

    expect($this->ordering->markPaidByHelloAsso($order, 555))->toBeTrue()
        ->and($this->ordering->markPaidByHelloAsso($order->fresh(), 555))->toBeFalse();

    expect($order->fresh()->only(['helloasso_order_id']))->toBe(['helloasso_order_id' => 555])
        ->and($order->fresh()->payment_method)->toBe(TreePaymentMethod::HelloAsso);

    Mail::assertSentCount(1);
});

test('a helloasso payment cannot be unmarked by hand', function () {
    $order = TreeOrder::factory()->for($this->user)->paid(TreePaymentMethod::HelloAsso)->create();

    $this->ordering->unmarkCheque($order);
})->throws(InvalidArgumentException::class);

test('only paid orders can be picked up', function () {
    $paid = TreeOrder::factory()->for($this->user)->paid()->create();

    $this->ordering->setPickedUp($paid, true);
    expect($paid->fresh()->isPickedUp())->toBeTrue();

    $this->ordering->setPickedUp($paid, false);
    expect($paid->fresh()->isPickedUp())->toBeFalse();

    expect(fn () => $this->ordering->setPickedUp(TreeOrder::factory()->create(), true))->toThrow(InvalidArgumentException::class);
});

test('the mails carry the order form and the pickup slip', function () {
    $order = TreeOrder::factory()->for($this->user)->paid()->withLine('moyen', 1, 3500)->create();

    expect((new TreeOrderPlaced($order))->attachments())->toHaveCount(1)
        ->and((new TreeOrderPaid($order))->attachments())->toHaveCount(1);

    (new TreeOrderPlaced($order))->assertSeeInHtml($order->number);
    (new TreeOrderPaid($order))->assertSeeInHtml($order->number);
});
