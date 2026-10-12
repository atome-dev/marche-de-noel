<?php

namespace App\Snapshots;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Snapshots of the functional data (accounts, stands, planning, recipes,
 * orders, raffle, documents), stored as gzipped JSON on the private disk.
 *
 * Uploaded files (photos, PDFs, documents) are not part of a snapshot.
 */
class SnapshotStore
{
    public const DISK = 'local';

    public const DIRECTORY = 'snapshots';

    public const LAST_CHECK = 'last-check.json';

    /**
     * Tables of a snapshot, parents first. Sessions, cache, queues and
     * password reset tokens are technical and left out.
     *
     * @var list<string>
     */
    public const TABLES = [
        'users',
        'school_classes',
        'school_class_user',
        'stands',
        'stand_manager',
        'school_class_stand',
        'slots',
        'school_class_slot',
        'registrations',
        'recipe_categories',
        'recipes',
        'recipe_school_class',
        'recipe_ingredients',
        'recipe_photos',
        'commitments',
        'ingredient_orders',
        'raffle_prizes',
        'documents',
    ];

    /**
     * Write a snapshot. Returns its path, or null when nothing changed since
     * the latest one.
     */
    public function create(): ?string
    {
        $startedAt = microtime(true);
        $tables = $this->export();
        $content = json_encode($tables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $rows = array_map(count(...), $tables);

        $latest = $this->latest();
        $now = CarbonImmutable::now();
        $path = null;

        if ($latest === null || $this->read($latest) !== $content) {
            $path = self::DIRECTORY.'/'.$now->format('Y-m-d').'/snapshot-'.$now->format('Y-m-d_H-i-s').'.json.gz';

            $this->disk()->put($path, (string) gzencode($content, 6));
        }

        $this->disk()->put(self::DIRECTORY.'/'.self::LAST_CHECK, (string) json_encode([
            'checked_at' => $now->toIso8601String(),
            'changed' => $path !== null,
        ]));

        $this->log($path !== null ? 'written' : 'unchanged', [
            'file' => basename($path ?? $latest),
            'size' => $path !== null ? $this->disk()->size($path) : null,
            'rows' => array_sum($rows),
            'tables' => $rows,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        return $path;
    }

    /**
     * When the data was last compared with the latest snapshot, and whether a
     * new snapshot was written.
     *
     * @return array{checked_at: CarbonImmutable, changed: bool}|null
     */
    public function lastCheck(): ?array
    {
        $check = json_decode((string) $this->disk()->get(self::DIRECTORY.'/'.self::LAST_CHECK), true);

        if (! is_array($check) || ! isset($check['checked_at'])) {
            return null;
        }

        return ['checked_at' => CarbonImmutable::parse($check['checked_at']), 'changed' => (bool) ($check['changed'] ?? false)];
    }

    /**
     * Write a line in the dedicated daily log (storage/logs/snapshots-*.log).
     *
     * @param  array<string, mixed>  $context
     */
    public function log(string $event, array $context = [], string $level = 'info'): void
    {
        Log::channel('snapshots')->log($level, __('commands.snapshot.log.'.$event), array_filter([
            ...$context,
            // Who triggered it: the scheduler or a command line, or an administrator on the site.
            'origin' => app()->runningInConsole() ? 'console' : 'site',
            'user' => Auth::id(),
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    /**
     * Every snapshot path, oldest first.
     *
     * @return list<string>
     */
    public function all(): array
    {
        $paths = array_filter(
            $this->disk()->allFiles(self::DIRECTORY),
            fn (string $path): bool => str_ends_with($path, '.json.gz'),
        );

        $paths = array_values($paths);
        sort($paths);

        return $paths;
    }

    /**
     * Snapshots with their date and size, newest first.
     *
     * @return list<array{name: string, path: string, taken_at: CarbonImmutable, size: int}>
     */
    public function describe(): array
    {
        return array_map(fn (string $path): array => [
            'name' => basename($path),
            'path' => $path,
            // File names carry the time of the snapshot, in the application timezone.
            'taken_at' => CarbonImmutable::createFromFormat('Y-m-d_H-i-s', substr(basename($path, '.json.gz'), strlen('snapshot-')), config('app.timezone')),
            'size' => $this->disk()->size($path),
        ], array_reverse($this->all()));
    }

    public function latest(): ?string
    {
        $paths = $this->all();

        return $paths === [] ? null : $paths[count($paths) - 1];
    }

    /**
     * Find a snapshot from its file name, its path or the word "latest".
     */
    public function find(string $name): ?string
    {
        if ($name === 'latest') {
            return $this->latest();
        }

        foreach ($this->all() as $path) {
            if ($path === $name || basename($path) === $name || basename($path, '.json.gz') === $name) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Delete the snapshots older than the given number of days.
     *
     * @return int number of deleted snapshots
     */
    public function prune(int $days): int
    {
        $limit = CarbonImmutable::now()->subDays($days)->format('Y-m-d_H-i-s');
        $deleted = 0;

        foreach ($this->all() as $path) {
            if (substr(basename($path, '.json.gz'), strlen('snapshot-')) < $limit) {
                $this->disk()->delete($path);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->log('pruned', ['deleted' => $deleted, 'retention_days' => $days]);
        }

        foreach ($this->disk()->directories(self::DIRECTORY) as $directory) {
            if ($this->disk()->allFiles($directory) === []) {
                $this->disk()->deleteDirectory($directory);
            }
        }

        return $deleted;
    }

    /**
     * Replace the functional data by the content of a snapshot.
     *
     * @return int number of restored rows
     */
    public function restore(string $path): int
    {
        /** @var array<string, list<array<string, mixed>>> $tables */
        $tables = json_decode($this->read($path), true, flags: JSON_THROW_ON_ERROR);
        $restored = 0;

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($tables, &$restored): void {
                foreach (array_reverse(self::TABLES) as $table) {
                    DB::table($table)->delete();
                }

                foreach (self::TABLES as $table) {
                    // A column added or removed since the snapshot must not break the restore.
                    $columns = array_flip(Schema::getColumnListing($table));

                    foreach (array_chunk($tables[$table] ?? [], 200) as $rows) {
                        DB::table($table)->insert(array_map(fn (array $row): array => array_intersect_key($row, $columns), $rows));
                        $restored += count($rows);
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->log('restored', ['file' => basename($path), 'rows' => $restored], 'warning');

        return $restored;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function export(): array
    {
        $tables = [];

        foreach (self::TABLES as $table) {
            $query = DB::table($table);

            // A stable order makes two snapshots of the same data identical.
            foreach (array_slice(Schema::getColumnListing($table), 0, 2) as $column) {
                $query->orderBy($column);
            }

            $tables[$table] = array_values($query->get()->map(fn (object $row): array => (array) $row)->all());
        }

        return $tables;
    }

    private function read(string $path): string
    {
        $content = gzdecode((string) $this->disk()->get($path));

        if ($content === false) {
            throw new RuntimeException("Unreadable snapshot [$path].");
        }

        return $content;
    }

    private function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }
}
