<?php

namespace App\Snapshots;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
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
        $content = json_encode($this->export(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $latest = $this->latest();

        if ($latest !== null && $this->read($latest) === $content) {
            return null;
        }

        $now = CarbonImmutable::now();
        $path = self::DIRECTORY.'/'.$now->format('Y-m-d').'/snapshot-'.$now->format('Y-m-d_H-i-s').'.json.gz';

        $this->disk()->put($path, (string) gzencode($content, 6));

        return $path;
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
