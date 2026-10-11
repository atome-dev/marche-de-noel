<?php

use App\Cookies\CookieOrdering;
use App\Enums\StandType;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Stand;
use App\Models\User;
use OpenSpout\Reader\XLSX\Reader;

beforeEach(function () {
    config([
        'marche.orders.ingredients' => ['Poudre d\'amande', 'Poudre de noisette'],
        'marche.orders.quantities' => [1, 2, 3],
        'marche.orders.deadline' => null,
    ]);

    $this->manager = User::factory()->create();
    Stand::factory()->ofType(StandType::Cookies)->create()->managers()->attach($this->manager);

    $recipe = Recipe::factory()->create();
    RecipeIngredient::factory()->for($recipe)->orderable('poudre-damande', 200)->create();
    RecipeIngredient::factory()->for($recipe)->orderable('poudre-de-noisette', 100)->create();

    $ordering = app(CookieOrdering::class);
    $camille = User::factory()->create(['first_name' => 'Camille', 'last_name' => 'Martin', 'email' => 'camille@example.com']);
    [$commitment] = $ordering->commit($camille, $recipe, 3);
    $ordering->order($commitment, 'poudre-damande');
    $ordering->order($commitment, 'poudre-de-noisette');

    $dominique = User::factory()->create(['first_name' => 'Dominique', 'last_name' => 'Arnaud']);
    [$commitment] = $ordering->commit($dominique, $recipe, 3);
    $ordering->order($commitment, 'poudre-damande');
});

test('volunteers cannot see the purchasing summary nor its exports', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('cookies.orders'))->assertForbidden();
    $this->get(route('cookies.orders.xlsx'))->assertForbidden();
    $this->get(route('cookies.orders.pdf'))->assertForbidden();
});

test('the summary shows families, subtotals and totals', function () {
    $this->actingAs($this->manager)
        ->get(route('cookies.orders'))
        ->assertOk()
        ->assertSeeInOrder(['Arnaud', 'Martin'])
        ->assertSee('600 g')
        ->assertSee('300 g')
        ->assertSee('900 g')
        ->assertSee('1,2 kg')
        ->assertSee('1,5 kg');
});

test('the summary can be exported to xlsx', function () {
    $response = $this->actingAs($this->manager)->get(route('cookies.orders.xlsx'));

    $response->assertOk()->assertDownload();

    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $response->streamedContent());

    $reader = new Reader;
    $reader->open($path);
    $rows = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
    }

    $reader->close();
    unlink($path);

    expect($rows[0])->toBe(['Famille', 'Adresse électronique', 'Poudre d\'amande (g)', 'Poudre de noisette (g)', 'Sous-total (g)'])
        ->and($rows[1])->toBe(['Arnaud Dominique', $rows[1][1], 600, 0, 600])
        ->and($rows[2])->toBe(['Martin Camille', 'camille@example.com', 600, 300, 900])
        ->and($rows[3])->toBe(['Total', '', 1200, 300, 1500]);
});

test('the summary can be exported to pdf', function () {
    $response = $this->actingAs($this->manager)->get(route('cookies.orders.pdf'));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');

    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});
