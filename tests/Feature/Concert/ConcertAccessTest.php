<?php

use App\Enums\StandType;
use App\Models\Stand;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

test('managers of a concert stand manage the concert', function () {
    $manager = User::factory()->create();
    Stand::factory()->ofType(StandType::Concert)->create()->managers()->attach($manager);

    expect(Gate::forUser($manager)->allows('manage-concert'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->create())->allows('manage-concert'))->toBeFalse();
});

test('managers of another stand do not manage the concert', function () {
    $manager = User::factory()->create();
    Stand::factory()->ofType(StandType::TreeOrder)->create()->managers()->attach($manager);

    expect(Gate::forUser($manager)->allows('manage-concert'))->toBeFalse();
});

test('the reference concert stand becomes a concert stand', function () {
    $stand = Stand::factory()->ofType(StandType::Generic)->create(['name' => 'Concert du Grand Orchestre de l\'école']);

    (require database_path('migrations/2026_10_17_000001_set_concert_stand_type.php'))->up();

    expect($stand->fresh()->type)->toBe(StandType::Concert)
        ->and(DB::table('stands')->where('type', 'concert')->count())->toBe(1);
});
