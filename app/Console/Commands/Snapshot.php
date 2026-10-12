<?php

namespace App\Console\Commands;

use App\Snapshots\SnapshotStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('marche:snapshot')]
#[Description('Enregistre un instantané des données (comptes, stands, planning, commandes…) et efface les plus anciens')]
class Snapshot extends Command
{
    public function handle(SnapshotStore $snapshots): int
    {
        try {
            $path = $snapshots->create();
        } catch (Throwable $exception) {
            // Logged, then rethrown: the scheduler must not report a success.
            $snapshots->log('failed', ['error' => $exception->getMessage()], 'error');

            throw $exception;
        }

        $this->info($path === null
            ? __('commands.snapshot.unchanged')
            : __('commands.snapshot.done', ['path' => $path]));

        $deleted = $snapshots->prune((int) config('marche.snapshots.retention_days'));

        if ($deleted > 0) {
            $this->line(trans_choice('commands.snapshot.pruned', $deleted, ['count' => $deleted]));
        }

        return self::SUCCESS;
    }
}
