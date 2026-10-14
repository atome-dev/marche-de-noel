<?php

use App\Enums\StandType;
use App\Enums\TreePaymentMethod;
use App\Livewire\TreeOrders\Summary;
use App\Models\Stand;
use App\Models\TreeOrder;
use App\Models\User;
use App\TreeOrders\TreeOrdersSummary;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;

beforeEach(function () {
    config(['trees.sizes' => [
        ['key' => 'lutins', 'name' => 'Le sapin des lutins', 'height' => '80/100 cm', 'price' => 27],
        ['key' => 'moyen', 'name' => 'Le sapin moyen', 'height' => '125/150 cm', 'price' => 35],
    ]]);

    TreeOrder::factory()->paid(TreePaymentMethod::Cheque)->withLine('lutins', 2, 2700, 'Le sapin des lutins')->withLine('moyen', 1, 3500, 'Le sapin moyen')->create(['customer_name' => 'Camille Martin']);
    TreeOrder::factory()->paid(TreePaymentMethod::HelloAsso)->pickedUp()->withLine('moyen', 3, 3500, 'Le sapin moyen')->create(['customer_name' => 'Dominique Arnaud']);
    TreeOrder::factory()->withLine('moyen', 5, 3500)->create();
    TreeOrder::factory()->paid()->cancelled()->withLine('lutins', 4, 2700)->create();

    $this->manager = User::factory()->create();
    Stand::factory()->ofType(StandType::TreeOrder)->create()->managers()->attach($this->manager);
    $this->actingAs($this->manager);
});

test('only paid, not cancelled orders are counted, by size', function () {
    $summary = app(TreeOrdersSummary::class);

    expect(array_map(fn (array $row) => [$row['key'], $row['quantity'], $row['amount_cents']], $summary->rows()))->toBe([
        ['lutins', 2, 5400],
        ['moyen', 4, 14000],
    ])
        ->and($summary->totalQuantity())->toBe(6)
        ->and($summary->totalAmountCents())->toBe(19400)
        ->and($summary->pendingCount())->toBe(1)
        ->and($summary->pendingAmountCents())->toBe(17500)
        ->and($summary->paidOrders()->pluck('customer_name')->all())->toBe(['Camille Martin', 'Dominique Arnaud']);
});

test('the summary page shows the totals and the pending orders', function () {
    Livewire::test(Summary::class)
        ->assertSee('Le sapin des lutins')
        ->assertSee("194\u{00A0}€", false)
        ->assertSee(trans_choice('trees.summary.pending', 1, ['count' => 1, 'amount' => "175\u{00A0}€"]), false);
});

test('the summary is exported to xlsx with the detail of the orders', function () {
    $response = $this->get(route('trees.summary.xlsx'))->assertOk()->assertDownload();

    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $response->streamedContent());

    $reader = new Reader;
    $reader->open($path);
    $sheets = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        $sheets[] = array_map(fn ($row) => $row->toArray(), iterator_to_array($sheet->getRowIterator(), false));
    }
    $reader->close();

    expect($sheets[0][1])->toBe(['Le sapin des lutins (80/100 cm)', 2, 54])
        ->and(end($sheets[0]))->toBe([__('trees.summary.total'), 6, 194])
        ->and($sheets[1])->toHaveCount(3)
        ->and($sheets[1][1][1])->toBe('Camille Martin')
        ->and(array_slice($sheets[1][1], 4, 2))->toBe([2, 1]);
});

test('the summary is exported to pdf', function () {
    $this->get(route('trees.summary.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('volunteers cannot export the summary', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('trees.summary.xlsx'))->assertForbidden();
    $this->get(route('trees.summary.pdf'))->assertForbidden();
});
