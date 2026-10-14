<?php

use App\Enums\StandType;
use App\Enums\TreeOrderStatus;
use App\Enums\TreePaymentMethod;
use App\Models\Stand;
use App\Models\TreeOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    config(['trees.deadline' => '2026-11-29', 'marche.timezone' => 'Europe/Paris']);
    $this->travelTo('2026-11-20 10:00:00');
});

test('the order number is derived from its id', function () {
    $order = TreeOrder::factory()->create();

    expect($order->number)->toBe('S-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT));
});

test('the status follows payment, pickup, cancellation and the deadline', function () {
    $pending = TreeOrder::factory()->create();
    $paid = TreeOrder::factory()->paid(TreePaymentMethod::Cheque)->create();
    $pickedUp = TreeOrder::factory()->paid(TreePaymentMethod::HelloAsso)->pickedUp()->create();
    $cancelled = TreeOrder::factory()->cancelled()->create();

    expect($pending->status())->toBe(TreeOrderStatus::Pending)
        ->and($paid->status())->toBe(TreeOrderStatus::Paid)
        ->and($pickedUp->status())->toBe(TreeOrderStatus::PickedUp)
        ->and($cancelled->status())->toBe(TreeOrderStatus::Cancelled);

    $this->travelTo('2026-12-01 10:00:00');

    expect($pending->status())->toBe(TreeOrderStatus::Expired)
        ->and($paid->status())->toBe(TreeOrderStatus::Paid);
});

test('orders can be filtered by status', function (TreeOrderStatus $status, int $count) {
    TreeOrder::factory()->create();
    TreeOrder::factory()->paid(TreePaymentMethod::Cheque)->create();
    TreeOrder::factory()->paid(TreePaymentMethod::Cheque)->pickedUp()->create();
    TreeOrder::factory()->cancelled()->create();

    expect(TreeOrder::query()->withStatus($status)->count())->toBe($count);
})->with([
    'pending' => [TreeOrderStatus::Pending, 1],
    'paid' => [TreeOrderStatus::Paid, 1],
    'picked up' => [TreeOrderStatus::PickedUp, 1],
    'cancelled' => [TreeOrderStatus::Cancelled, 1],
    'expired' => [TreeOrderStatus::Expired, 0],
]);

test('pending orders are expired once the deadline has passed', function () {
    TreeOrder::factory()->create();
    $this->travelTo('2026-12-01 10:00:00');

    expect(TreeOrder::query()->withStatus(TreeOrderStatus::Pending)->count())->toBe(0)
        ->and(TreeOrder::query()->withStatus(TreeOrderStatus::Expired)->count())->toBe(1);
});

test('orders can be searched by name or number', function () {
    $martin = TreeOrder::factory()->create(['customer_name' => 'Camille Martin']);
    TreeOrder::factory()->create(['customer_name' => 'Dominique Arnaud']);

    expect(TreeOrder::query()->search('martin')->pluck('id')->all())->toBe([$martin->id])
        ->and(TreeOrder::query()->search($martin->number)->pluck('id')->all())->toBe([$martin->id])
        ->and(TreeOrder::query()->search((string) $martin->id)->pluck('id')->all())->toBe([$martin->id]);
});

test('tree stand managers and administrators manage the tree orders', function () {
    $manager = User::factory()->create();
    Stand::factory()->ofType(StandType::TreeOrder)->create()->managers()->attach($manager);
    $otherManager = User::factory()->create();
    Stand::factory()->ofType(StandType::Raffle)->create()->managers()->attach($otherManager);

    expect(Gate::forUser($manager)->allows('manage-tree-orders'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->admin()->create())->allows('manage-tree-orders'))->toBeTrue()
        ->and(Gate::forUser($otherManager)->allows('manage-tree-orders'))->toBeFalse()
        ->and(Gate::forUser(User::factory()->create())->allows('manage-tree-orders'))->toBeFalse();
});

test('families only reach their own orders', function () {
    $owner = User::factory()->create();
    $order = TreeOrder::factory()->for($owner)->create();
    $manager = User::factory()->create();
    Stand::factory()->ofType(StandType::TreeOrder)->create()->managers()->attach($manager);

    expect(Gate::forUser($owner)->allows('view', $order))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('update', $order))->toBeTrue()
        ->and(Gate::forUser(User::factory()->create())->allows('view', $order))->toBeFalse()
        ->and(Gate::forUser(User::factory()->create())->allows('update', $order))->toBeFalse()
        ->and(Gate::forUser($manager)->allows('view', $order))->toBeTrue();
});

test('the reference tree stand becomes a tree order stand', function () {
    $stand = Stand::factory()->create(['name' => 'Sapins du Morvan', 'type' => StandType::Generic]);

    $migration = require database_path('migrations/2026_10_14_000002_set_tree_order_stand_type.php');
    $migration->up();

    expect($stand->fresh()->type)->toBe(StandType::TreeOrder)
        ->and((require database_path('data/stands.php'))['Sapins du Morvan'])->toBe(StandType::TreeOrder);
});

test('lines keep the label and price of the order time', function () {
    $order = TreeOrder::factory()->withLine('lutins', 2, 2700)->create();

    expect($order->lines)->toHaveCount(1)
        ->and($order->lines->first()->only(['size_key', 'quantity', 'unit_price_cents']))
        ->toBe(['size_key' => 'lutins', 'quantity' => 2, 'unit_price_cents' => 2700])
        ->and(DB::table('tree_order_lines')->count())->toBe(1);
});
