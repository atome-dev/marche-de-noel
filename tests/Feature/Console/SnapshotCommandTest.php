<?php

use App\Models\Commitment;
use App\Models\IngredientOrder;
use App\Models\RafflePrize;
use App\Models\Registration;
use App\Models\Slot;
use App\Models\Stand;
use App\Models\User;
use App\Snapshots\SnapshotStore;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Schedule as ScheduleFacade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(SnapshotStore::DISK);

    $this->travelTo('2026-11-20 10:00:00');

    $this->user = User::factory()->create(['email' => 'camille@example.com']);
    $this->stand = Stand::factory()->create(['name' => 'Buvette']);
    $this->stand->managers()->attach($this->user);
    $this->slot = Slot::factory()->for($this->stand)->create();
    Registration::factory()->for($this->slot)->for($this->user)->create();
    IngredientOrder::factory()->for(Commitment::factory()->for($this->user))->create();
    RafflePrize::factory()->create(['label' => 'Panier garni']);

    $this->snapshots = app(SnapshotStore::class);
});

test('a snapshot stores the functional tables as gzipped json', function () {
    $this->artisan('marche:snapshot')->assertSuccessful();

    $path = 'snapshots/2026-11-20/snapshot-2026-11-20_10-00-00.json.gz';

    expect($this->snapshots->all())->toBe([$path]);

    $tables = json_decode(gzdecode(Storage::disk(SnapshotStore::DISK)->get($path)), true);

    expect(array_keys($tables))->toBe(SnapshotStore::TABLES)
        ->and($tables['users'][0]['email'])->toBe('camille@example.com')
        ->and($tables['stands'][0]['name'])->toBe('Buvette')
        ->and($tables['registrations'])->toHaveCount(1)
        ->and($tables['ingredient_orders'])->toHaveCount(1)
        ->and($tables['raffle_prizes'][0]['label'])->toBe('Panier garni');
});

test('every table of the database is either in the snapshot or known as technical', function () {
    $technical = ['migrations', 'sessions', 'password_reset_tokens', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

    $tables = collect(Schema::getTableListing(schemaQualified: false))->reject(fn (string $table) => str_starts_with($table, 'sqlite_'));

    expect($tables->diff($technical)->sort()->values()->all())->toBe(collect(SnapshotStore::TABLES)->sort()->values()->all());
});

test('nothing is written when the data did not change', function () {
    $this->artisan('marche:snapshot')->assertSuccessful();

    $this->travelTo('2026-11-20 10:15:00');
    $this->artisan('marche:snapshot')->expectsOutput(__('commands.snapshot.unchanged'))->assertSuccessful();

    expect($this->snapshots->all())->toHaveCount(1);

    $this->stand->update(['name' => 'Buvette chaude']);

    $this->travelTo('2026-11-20 10:30:00');
    $this->artisan('marche:snapshot')->assertSuccessful();

    expect($this->snapshots->all())->toHaveCount(2)
        ->and($this->snapshots->latest())->toBe('snapshots/2026-11-20/snapshot-2026-11-20_10-30-00.json.gz');
});

test('snapshots older than the retention are deleted', function () {
    config(['marche.snapshots.retention_days' => 2]);

    $this->artisan('marche:snapshot');

    $this->travelTo('2026-11-21 10:00:00');
    $this->stand->update(['name' => 'B']);
    $this->artisan('marche:snapshot');

    $this->travelTo('2026-11-22 10:00:01');
    $this->stand->update(['name' => 'C']);
    $this->artisan('marche:snapshot');

    expect($this->snapshots->all())->toBe([
        'snapshots/2026-11-21/snapshot-2026-11-21_10-00-00.json.gz',
        'snapshots/2026-11-22/snapshot-2026-11-22_10-00-01.json.gz',
    ]);

    Storage::disk(SnapshotStore::DISK)->assertMissing('snapshots/2026-11-20');
});

test('the snapshot is scheduled every fifteen minutes', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'marche:snapshot'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('*/15 * * * *');
});

describe('restore', function () {
    beforeEach(function () {
        $this->artisan('marche:snapshot');
        $this->travelTo('2026-11-20 11:00:00');

        // The mishap: slots (and their registrations) and a prize are lost, a stand is renamed.
        $this->slot->delete();
        RafflePrize::query()->delete();
        $this->stand->update(['name' => 'Renommé']);
        Stand::factory()->create(['name' => 'Créé après']);

        $this->confirmation = __('commands.restore_snapshot.confirm', ['word' => __('commands.restore_snapshot.word')]);
    });

    test('without a name the available snapshots are listed', function () {
        $this->artisan('marche:restore-snapshot')
            ->expectsOutput('  snapshot-2026-11-20_10-00-00.json.gz')
            ->assertSuccessful();

        expect(Slot::count())->toBe(0);
    });

    test('nothing changes without the exact confirmation word', function () {
        $this->artisan('marche:restore-snapshot', ['snapshot' => 'latest'])
            ->expectsQuestion($this->confirmation, 'oui')
            ->assertFailed();

        expect(Slot::count())->toBe(0)
            ->and($this->snapshots->all())->toHaveCount(1);
    });

    test('an unknown snapshot is refused', function () {
        $this->artisan('marche:restore-snapshot', ['snapshot' => 'snapshot-1999.json.gz', '--force' => true])->assertFailed();

        expect(Slot::count())->toBe(0);
    });

    test('a snapshot brings the data back and keeps the current state as a new snapshot', function () {
        $this->artisan('marche:restore-snapshot', ['snapshot' => 'snapshot-2026-11-20_10-00-00.json.gz'])
            ->expectsQuestion($this->confirmation, __('commands.restore_snapshot.word'))
            ->assertSuccessful();

        expect(Stand::pluck('name')->all())->toBe(['Buvette'])
            ->and(Slot::sole()->id)->toBe($this->slot->id)
            ->and(Registration::sole()->user_id)->toBe($this->user->id)
            ->and(RafflePrize::sole()->label)->toBe('Panier garni')
            ->and(IngredientOrder::count())->toBe(1)
            ->and($this->stand->managers()->count())->toBe(1)
            ->and(User::sole()->email)->toBe('camille@example.com')
            // The state before the restore was saved first.
            ->and($this->snapshots->all())->toHaveCount(2)
            ->and($this->snapshots->latest())->toBe('snapshots/2026-11-20/snapshot-2026-11-20_11-00-00.json.gz');
    });

    test('the force option skips the confirmation', function () {
        $this->artisan('marche:restore-snapshot', ['snapshot' => 'latest', '--force' => true])->assertSuccessful();

        expect(Slot::count())->toBe(1);
    });
});

test('the purge deletes the snapshots', function () {
    $this->artisan('marche:snapshot');

    $this->artisan('marche:purge')
        ->expectsQuestion(__('commands.purge.confirm', ['word' => __('commands.purge.word')]), __('commands.purge.word'))
        ->assertSuccessful();

    expect($this->snapshots->all())->toBe([]);
});

test('the heartbeat address is pinged after a successful snapshot, when configured', function (?string $url, array $expected) {
    config(['marche.snapshots.heartbeat_url' => $url]);

    $pinged = [];
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('request')->andReturnUsing(function (string $method, string $url) use (&$pinged) {
        $pinged[] = $url;

        return new Response;
    });
    app()->instance(Client::class, $client);

    $schedule = new Schedule;
    ScheduleFacade::swap($schedule);
    require base_path('routes/console.php');

    $event = collect($schedule->events())->sole(fn ($event) => str_contains($event->command, 'marche:snapshot'));
    $event->exitCode = 0;
    $event->callAfterCallbacks(app());

    expect($pinged)->toBe($expected);
})->with([
    'configured' => ['https://forge.example/heartbeat/abc', ['https://forge.example/heartbeat/abc']],
    'not configured' => [null, []],
]);
