<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Snapshots\SnapshotStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

#[Signature('marche:purge')]
#[Description('Efface toutes les données personnelles de l’édition (purge annuelle RGPD)')]
class Purge extends Command
{
    /**
     * Tables emptied by the yearly purge, children first.
     * Reference data is kept: school classes, the (almost fixed) list of
     * stands with their classes, and the recipes with their biscuit families.
     * Slots, registrations, managers, commitments and orders go.
     *
     * @var list<string>
     */
    public const TABLES = [
        'tree_order_lines',
        'tree_orders',
        'documents',
        'raffle_prizes',
        'ingredient_orders',
        'commitments',
        'registrations',
        'school_class_slot',
        'slots',
        'stand_manager',
        'school_class_user',
        'sessions',
        'password_reset_tokens',
        'users',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function handle(): int
    {
        $this->warn(__('commands.purge.warning'));

        $word = __('commands.purge.word');

        if ($this->ask(__('commands.purge.confirm', ['word' => $word])) !== $word) {
            $this->line(__('commands.purge.aborted'));

            return self::FAILURE;
        }

        DB::transaction(function (): void {
            foreach (self::TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }
        });

        // Yearly documents are uploaded again by the administrator.
        Storage::disk(Document::DISK)->deleteDirectory(Document::DIRECTORY);

        // Snapshots hold personal data: they go with the rest.
        Storage::disk(SnapshotStore::DISK)->deleteDirectory(SnapshotStore::DIRECTORY);

        $this->info(__('commands.purge.done'));
        $this->line(__('commands.purge.next_steps'));

        return self::SUCCESS;
    }
}
