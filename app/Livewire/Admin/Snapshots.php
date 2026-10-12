<?php

namespace App\Livewire\Admin;

use App\Snapshots\SnapshotLog;
use App\Snapshots\SnapshotStore;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Snapshots of the data: list, take one now, download, restore.
 *
 * @property-read list<array{name: string, path: string, taken_at: CarbonImmutable, size: int}> $snapshots
 * @property-read array{checked_at: CarbonImmutable, changed: bool}|null $lastCheck
 * @property-read list<array{at: CarbonImmutable, level: string, message: string, context: array<string, mixed>, changes: array<string, int>}> $log
 */
class Snapshots extends Component
{
    /** Number of snapshots listed: one day of 15 minute snapshots. */
    public const LISTED = 96;

    public function mount(): void
    {
        $this->authorize('admin');
    }

    public function create(SnapshotStore $store): void
    {
        $this->authorize('admin');

        $created = $store->create() !== null;

        unset($this->snapshots, $this->lastCheck, $this->log);

        Flux::toast(
            variant: $created ? 'success' : null,
            text: $created ? __('admin.snapshots.created') : __('admin.snapshots.unchanged'),
        );
    }

    public function restore(string $name, SnapshotStore $store): void
    {
        $this->authorize('admin');

        $path = $store->find($name);

        if ($path === null) {
            unset($this->snapshots, $this->lastCheck, $this->log);

            Flux::toast(variant: 'danger', text: __('admin.snapshots.not_found'));

            return;
        }

        $date = $this->formatDate(collect($store->describe())->firstWhere('path', $path)['taken_at']);

        // The current state stays recoverable if the wrong snapshot was chosen.
        $store->create();
        $store->restore($path);

        unset($this->snapshots, $this->lastCheck, $this->log);

        Flux::toast(variant: 'success', text: __('admin.snapshots.restored', ['date' => $date]));
    }

    /**
     * @return list<array{name: string, path: string, taken_at: CarbonImmutable, size: int}>
     */
    #[Computed]
    public function snapshots(): array
    {
        return app(SnapshotStore::class)->describe();
    }

    /**
     * @return array{checked_at: CarbonImmutable, changed: bool}|null
     */
    #[Computed]
    public function lastCheck(): ?array
    {
        return app(SnapshotStore::class)->lastCheck();
    }

    /**
     * @return list<array{at: CarbonImmutable, level: string, message: string, context: array<string, mixed>, changes: array<string, int>}>
     */
    #[Computed]
    public function log(): array
    {
        return app(SnapshotLog::class)->today();
    }

    public function formatDate(CarbonImmutable $date): string
    {
        return $date->setTimezone(config('marche.timezone'))->translatedFormat('l j F Y \à H:i');
    }

    public function render(): View
    {
        return view('livewire.admin.snapshots')
            ->title(__('admin.snapshots.title'));
    }
}
