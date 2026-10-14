<?php

use App\Enums\StandType;
use App\Enums\TreePaymentMethod;
use App\Models\Stand;
use App\Models\TreeOrder;
use App\Models\User;
use App\Support\Money;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->order = TreeOrder::factory()->for($this->owner)->withLine('moyen', 2, 3500)->create();
});

test('the family downloads the order form of its order', function () {
    $this->actingAs($this->owner)
        ->get(route('trees.documents.order-form', $this->order))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('bon-de-commande-'.$this->order->number.'.pdf');
});

test('another family cannot download the documents of an order', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('trees.documents.order-form', $this->order))->assertForbidden();
    $this->get(route('trees.documents.pickup-slip', $this->order))->assertForbidden();
});

test('tree stand managers download every document', function () {
    $manager = User::factory()->create();
    Stand::factory()->ofType(StandType::TreeOrder)->create()->managers()->attach($manager);

    $this->actingAs($manager)->get(route('trees.documents.order-form', $this->order))->assertOk();
});

test('the pickup slip exists only for paid orders', function () {
    $this->actingAs($this->owner)->get(route('trees.documents.pickup-slip', $this->order))->assertNotFound();

    $paid = TreeOrder::factory()->for($this->owner)->paid(TreePaymentMethod::HelloAsso)->withLine('lutins', 1, 2700)->create();

    $this->actingAs($this->owner)
        ->get(route('trees.documents.pickup-slip', $paid))
        ->assertOk()
        ->assertDownload('bon-de-retrait-'.$paid->number.'.pdf');
});

test('the order form shows the order details', function () {
    config(['trees.cheque.payee' => 'École Steiner']);

    $html = view('tree-orders.order-form-pdf', ['order' => $this->order->load('lines')])->render();

    expect($html)->toContain($this->order->number)
        ->toContain($this->order->customer_name)
        ->toContain('École Steiner')
        ->toContain(Money::format(7000));
});

test('amounts are formatted in euros', function () {
    expect(Money::format(2700))->toBe("27\u{00A0}€")
        ->and(Money::format(3550))->toBe("35,50\u{00A0}€")
        ->and(Money::format(123400))->toBe("1\u{202F}234\u{00A0}€");
});
