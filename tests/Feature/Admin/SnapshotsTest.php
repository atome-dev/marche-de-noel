<?php

use App\Livewire\Admin\Snapshots;
use App\Models\Stand;
use App\Models\User;
use App\Snapshots\SnapshotStore;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(SnapshotStore::DISK);
    config(['marche.timezone' => 'Europe/Paris']);

    $this->travelTo('2026-11-20 10:00:00');

    $this->admin = User::factory()->admin()->create();
    $this->stand = Stand::factory()->create(['name' => 'Buvette']);
    $this->store = app(SnapshotStore::class);
});

test('only administrators reach the snapshots', function () {
    $this->store->create();
    $name = basename($this->store->latest());

    $this->get(route('admin.snapshots'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create());
    $this->get(route('admin.snapshots'))->assertForbidden();
    $this->get(route('admin.snapshots.download', $name))->assertForbidden();
    Livewire::test(Snapshots::class)->assertForbidden();

    $this->actingAs($this->admin);
    $this->get(route('admin.snapshots'))->assertOk()->assertSeeLivewire(Snapshots::class);
});

test('snapshots are listed newest first, in local time', function () {
    $this->store->create();
    $this->travelTo('2026-11-20 10:15:00');
    $this->stand->update(['name' => 'Buvette chaude']);
    $this->store->create();

    $this->actingAs($this->admin);

    // 10:00 UTC is 11:00 in Paris in November.
    Livewire::test(Snapshots::class)
        ->assertSeeInOrder(['vendredi 20 novembre 2026 à 11:15', __('admin.snapshots.latest'), 'vendredi 20 novembre 2026 à 11:00']);
});

test('an administrator takes a snapshot now', function () {
    $this->actingAs($this->admin);

    Livewire::test(Snapshots::class)
        ->assertSee(__('admin.snapshots.empty'))
        ->call('create')
        ->assertDontSee(__('admin.snapshots.empty'))
        ->call('create');

    expect($this->store->all())->toHaveCount(1);
});

test('an administrator downloads a snapshot', function () {
    $this->store->create();
    $name = basename($this->store->latest());

    $this->actingAs($this->admin);

    $this->get(route('admin.snapshots.download', $name))
        ->assertOk()
        ->assertDownload($name);

    $this->get(route('admin.snapshots.download', 'snapshot-1999-01-01_00-00-00.json.gz'))->assertNotFound();
    $this->get(route('admin.snapshots.download', '..%2F..%2F.env'))->assertNotFound();
});

test('an administrator restores a snapshot and stays signed in', function () {
    $this->store->create();
    $name = basename($this->store->latest());

    $this->travelTo('2026-11-20 11:00:00');
    $this->stand->delete();
    Stand::factory()->create(['name' => 'Créé après']);

    $this->actingAs($this->admin);

    Livewire::test(Snapshots::class)->call('restore', $name)->assertOk();

    expect(Stand::pluck('name')->all())->toBe(['Buvette'])
        ->and(User::whereKey($this->admin->id)->exists())->toBeTrue()
        // The state before the restore was saved first.
        ->and($this->store->all())->toHaveCount(2);
});

test('restoring an unknown snapshot changes nothing', function () {
    $this->actingAs($this->admin);

    Livewire::test(Snapshots::class)->call('restore', 'snapshot-1999-01-01_00-00-00.json.gz')->assertOk();

    expect(Stand::pluck('name')->all())->toBe(['Buvette'])
        ->and($this->store->all())->toBe([]);
});

test('the page tells when the data was last checked', function () {
    $this->actingAs($this->admin);

    Livewire::test(Snapshots::class)->assertSee(__('admin.snapshots.never_checked'));

    $this->store->create();
    $this->travelTo('2026-11-20 10:15:00');
    $this->store->create();

    Livewire::test(Snapshots::class)
        ->assertSee('vendredi 20 novembre 2026 à 11:15')
        ->assertSee(__('admin.snapshots.last_check_unchanged'))
        ->assertDontSee(__('admin.snapshots.never_checked'));
});

describe('log of the day', function () {
    beforeEach(function () {
        $this->logBase = storage_path('framework/testing/snapshots-page.log');
        array_map(unlink(...), glob(storage_path('framework/testing/snapshots-page-*.log')) ?: []);

        config(['logging.channels.snapshots' => ['driver' => 'daily', 'path' => $this->logBase, 'level' => 'info', 'max_files' => 7]]);
        Log::forgetChannel('snapshots');

        // The logger stamps its lines with the real clock: the files are written here, as it would.
        $this->writeLog = function (string $at, string $level, string $message, array $context): void {
            file_put_contents(
                storage_path('framework/testing/snapshots-page-'.substr($at, 0, 10).'.log'),
                sprintf("[%s] production.%s: %s %s \n", $at, $level, $message, json_encode($context, JSON_UNESCAPED_UNICODE)),
                FILE_APPEND,
            );
        };

        $this->actingAs($this->admin);
    });

    afterEach(fn () => array_map(unlink(...), glob(storage_path('framework/testing/snapshots-page-*.log')) ?: []));

    test('an empty day is reported', function () {
        Livewire::test(Snapshots::class)->assertSee(__('admin.snapshots.log.empty'));
    });

    test('the runs of the day are listed newest first, with what changed', function () {
        $written = __('commands.snapshot.log.written');
        $unchanged = __('commands.snapshot.log.unchanged');

        ($this->writeLog)('2026-11-20 08:00:00', 'INFO', $written, ['file' => 'snapshot-2026-11-20_08-00-00.json.gz', 'size' => 20480, 'rows' => 100, 'tables' => ['stands' => 48, 'registrations' => 10], 'duration_ms' => 30, 'origin' => 'console']);
        ($this->writeLog)('2026-11-20 08:15:00', 'INFO', $unchanged, ['file' => 'snapshot-2026-11-20_08-00-00.json.gz', 'rows' => 100, 'tables' => ['stands' => 48, 'registrations' => 10], 'duration_ms' => 20, 'origin' => 'console']);
        ($this->writeLog)('2026-11-20 08:30:00', 'INFO', $written, ['file' => 'snapshot-2026-11-20_08-30-00.json.gz', 'size' => 21504, 'rows' => 101, 'tables' => ['stands' => 50, 'registrations' => 9], 'duration_ms' => 31, 'origin' => 'console']);
        ($this->writeLog)('2026-11-20 08:45:00', 'ERROR', __('commands.snapshot.log.failed'), ['error' => 'disque plein', 'origin' => 'console']);

        // 08:00 UTC is 09:00 in Paris in November.
        Livewire::test(Snapshots::class)
            ->assertSeeInOrder([
                '09:45', __('commands.snapshot.log.failed'), 'disque plein',
                '09:30', $written, 'snapshot-2026-11-20_08-30-00.json.gz', '21 Ko', '101 lignes', '31 ms',
                __('admin.snapshots.tables.stands').' +2', __('admin.snapshots.tables.registrations').' −1',
                '09:15', $unchanged,
                '09:00', $written,
            ])
            ->assertDontSee(__('admin.snapshots.log.empty'));
    });

    test('entries of the previous local day are left out, across the two files', function () {
        // Paris is one hour ahead: 22:30 UTC is still the 20th, 23:30 UTC is already the 21st.
        ($this->writeLog)('2026-11-20 22:30:00', 'INFO', 'Passage de la veille', ['tables' => ['stands' => 1]]);
        ($this->writeLog)('2026-11-20 23:30:00', 'INFO', 'Passage après minuit', ['tables' => ['stands' => 3]]);
        ($this->writeLog)('2026-11-21 00:10:00', 'INFO', 'Passage du matin', ['tables' => ['stands' => 3]]);

        $this->travelTo('2026-11-21 08:00:00');

        Livewire::test(Snapshots::class)
            ->assertSeeInOrder(['01:10', 'Passage du matin', '00:30', 'Passage après minuit', __('admin.snapshots.tables.stands').' +2'])
            ->assertDontSee('Passage de la veille');
    });

    test('an action from the site is flagged', function () {
        ($this->writeLog)('2026-11-20 09:00:00', 'WARNING', __('commands.snapshot.log.restored'), ['file' => 'snapshot-2026-11-20_08-00-00.json.gz', 'rows' => 100, 'origin' => 'site', 'user' => 1]);

        Livewire::test(Snapshots::class)
            ->assertSee(__('commands.snapshot.log.restored'))
            ->assertSee(__('admin.snapshots.log.from_site'));
    });

    test('real log lines are read back', function () {
        $this->travelBack();
        $this->store->create();

        Livewire::test(Snapshots::class)
            ->assertSee(__('commands.snapshot.log.written'))
            ->assertDontSee(__('admin.snapshots.log.empty'));
    });
});
