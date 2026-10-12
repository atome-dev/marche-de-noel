<?php

use App\Enums\StandType;
use App\Livewire\Raffle\Index;
use App\Models\RafflePrize;
use App\Models\Stand;
use App\Models\User;
use App\Raffle\PrizeList;
use Livewire\Livewire;

function raffleManager(): User
{
    $manager = User::factory()->create();
    Stand::factory()->create(['type' => StandType::Raffle])->managers()->attach($manager);

    return $manager;
}

/** @return array<int, string> label by displayed number */
function prizeNumbers(): array
{
    return RafflePrize::query()->ordered()->get()
        ->mapWithKeys(fn (RafflePrize $prize) => [$prize->displayNumber() => $prize->label])
        ->all();
}

test('guests cannot see the raffle', function () {
    $this->get(route('raffle.index'))->assertRedirect(route('login'));
});

test('every volunteer sees the prizes and winning tickets but cannot edit them', function () {
    RafflePrize::factory()->create(['label' => 'Panier garni', 'number' => 1, 'winning_ticket' => '0421']);

    $this->actingAs(User::factory()->create());

    Livewire::test(Index::class)
        ->assertSee('Panier garni')
        ->assertSee('0421')
        ->assertDontSee(__('raffle.form.add'))
        ->call('add')
        ->assertForbidden();
});

test('only raffle managers and administrators can edit the prizes', function (Closure $user, bool $allowed) {
    $prize = RafflePrize::factory()->create(['number' => 1]);

    $this->actingAs($user());

    foreach ([['rename', $prize->id, 'Autre'], ['validateList'], ['saveTicket', $prize->id, '12'], ['moveUp', $prize->id], ['reorder', $prize->id, 0], ['delete', $prize->id]] as $call) {
        $response = Livewire::test(Index::class)->call(...$call);

        $allowed ? $response->assertOk() : $response->assertForbidden();
    }
})->with([
    'volunteer' => [fn () => User::factory()->create(), false],
    'manager of another stand' => [function () {
        $user = User::factory()->create();
        Stand::factory()->create(['type' => StandType::Generic])->managers()->attach($user);

        return $user;
    }, false],
    'raffle manager' => [fn () => raffleManager(), true],
    'administrator' => [fn () => User::factory()->admin()->create(), true],
]);

test('a new prize takes the last rank of a draft list', function () {
    $this->actingAs(raffleManager());

    Livewire::test(Index::class)
        ->set('label', '  Panier garni ')->call('add')
        ->set('label', 'Livre')->call('add')
        ->assertHasNoErrors()
        ->assertSet('label', '');

    expect(prizeNumbers())->toBe([1 => 'Panier garni', 2 => 'Livre']);
});

test('a prize needs a label', function () {
    $this->actingAs(raffleManager());

    Livewire::test(Index::class)->set('label', '')->call('add')->assertHasErrors('label');

    expect(RafflePrize::count())->toBe(0);
});

test('moving a prize renumbers the draft list', function () {
    $this->actingAs(raffleManager());
    [$a, $b, $c] = collect(['A', 'B', 'C'])->map(fn (string $label) => app(PrizeList::class)->add($label))->all();

    Livewire::test(Index::class)->call('moveUp', $c->id);
    expect(prizeNumbers())->toBe([1 => 'A', 2 => 'C', 3 => 'B']);

    Livewire::test(Index::class)->call('moveDown', $a->id);
    expect(prizeNumbers())->toBe([1 => 'C', 2 => 'A', 3 => 'B']);

    // Drag and drop sends a zero-based index.
    Livewire::test(Index::class)->call('reorder', $b->id, 0);
    expect(prizeNumbers())->toBe([1 => 'B', 2 => 'C', 3 => 'A']);

    // Moving past the ends keeps the list consistent.
    Livewire::test(Index::class)->call('moveUp', $b->id)->call('moveDown', $a->id);
    expect(prizeNumbers())->toBe([1 => 'B', 2 => 'C', 3 => 'A']);
});

test('deleting a draft prize closes the gap', function () {
    $this->actingAs(raffleManager());
    [, $b] = collect(['A', 'B', 'C'])->map(fn (string $label) => app(PrizeList::class)->add($label))->all();

    Livewire::test(Index::class)->call('delete', $b->id);

    expect(prizeNumbers())->toBe([1 => 'A', 2 => 'C']);
});

test('validating the list freezes the numbers', function () {
    $this->actingAs(raffleManager());
    [$a, , $c] = collect(['A', 'B', 'C'])->map(fn (string $label) => app(PrizeList::class)->add($label))->all();

    Livewire::test(Index::class)
        ->call('validateList')
        ->assertSee(__('raffle.validated.badge'))
        ->call('moveUp', $c->id)
        ->call('reorder', $c->id, 0);

    expect(RafflePrize::query()->ordered()->pluck('number', 'label')->all())->toBe(['A' => 1, 'B' => 2, 'C' => 3]);

    // New prizes take the next numbers, and a deleted number is never reused.
    Livewire::test(Index::class)->set('label', 'D')->call('add')->call('delete', $a->id)->set('label', 'E')->call('add');

    expect(RafflePrize::query()->ordered()->pluck('number', 'label')->all())->toBe(['B' => 2, 'C' => 3, 'D' => 4, 'E' => 5]);
});

test('an empty list cannot be validated', function () {
    $this->actingAs(raffleManager());

    Livewire::test(Index::class)->call('validateList');

    expect(app(PrizeList::class)->isValidated())->toBeFalse()
        ->and(app(PrizeList::class)->add('A')->number)->toBeNull();
});

test('winning tickets are entered once the list is validated', function () {
    $this->actingAs(raffleManager());
    $prize = app(PrizeList::class)->add('A');

    Livewire::test(Index::class)->call('saveTicket', $prize->id, '0421')->assertForbidden();
    expect($prize->refresh()->winning_ticket)->toBeNull();

    Livewire::test(Index::class)->call('validateList')->call('saveTicket', $prize->id, ' 0421 ');
    expect($prize->refresh()->winning_ticket)->toBe('0421');

    Livewire::test(Index::class)->call('saveTicket', $prize->id, '');
    expect($prize->refresh()->winning_ticket)->toBeNull();
});

test('a prize can be renamed at any time', function () {
    $this->actingAs(raffleManager());
    $prize = app(PrizeList::class)->add('Panier');

    Livewire::test(Index::class)->call('validateList')->call('rename', $prize->id, 'Panier garni');

    expect($prize->refresh()->label)->toBe('Panier garni')
        ->and($prize->number)->toBe(1);
});

test('the purge deletes the prizes', function () {
    RafflePrize::factory()->count(2)->create();

    $this->artisan('marche:purge')
        ->expectsQuestion(__('commands.purge.confirm', ['word' => __('commands.purge.word')]), __('commands.purge.word'))
        ->assertSuccessful();

    expect(RafflePrize::count())->toBe(0);
});
