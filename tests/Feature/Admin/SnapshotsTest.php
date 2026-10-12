<?php

use App\Livewire\Admin\Snapshots;
use App\Models\Stand;
use App\Models\User;
use App\Snapshots\SnapshotStore;
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
