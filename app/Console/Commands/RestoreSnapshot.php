<?php

namespace App\Console\Commands;

use App\Snapshots\SnapshotStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('marche:restore-snapshot {snapshot? : Nom du fichier, ou « latest » pour le plus récent} {--list : Affiche les instantanés disponibles} {--force : Ne pas demander de confirmation (exécution depuis Forge)}')]
#[Description('Remplace les données par celles d’un instantané (marche:snapshot)')]
class RestoreSnapshot extends Command
{
    public function handle(SnapshotStore $snapshots): int
    {
        if ($this->option('list') || $this->argument('snapshot') === null) {
            return $this->list($snapshots);
        }

        $path = $snapshots->find((string) $this->argument('snapshot'));

        if ($path === null) {
            $this->error(__('commands.restore_snapshot.not_found', ['name' => $this->argument('snapshot')]));

            return self::FAILURE;
        }

        $this->warn(__('commands.restore_snapshot.warning', ['name' => basename($path)]));

        $word = __('commands.restore_snapshot.word');

        if (! $this->option('force') && $this->ask(__('commands.restore_snapshot.confirm', ['word' => $word])) !== $word) {
            $this->line(__('commands.restore_snapshot.aborted'));

            return self::FAILURE;
        }

        // The current state stays recoverable if the wrong snapshot was chosen.
        $backup = $snapshots->create();

        if ($backup !== null) {
            $this->line(__('commands.restore_snapshot.backup', ['name' => basename($backup)]));
        }

        $rows = $snapshots->restore($path);

        $this->info(__('commands.restore_snapshot.done', ['name' => basename($path), 'count' => $rows]));

        return self::SUCCESS;
    }

    private function list(SnapshotStore $snapshots): int
    {
        $paths = array_slice(array_reverse($snapshots->all()), 0, 40);

        if ($paths === []) {
            $this->line(__('commands.restore_snapshot.none'));

            return self::SUCCESS;
        }

        $this->line(__('commands.restore_snapshot.available'));

        foreach ($paths as $path) {
            $this->line('  '.basename($path));
        }

        return self::SUCCESS;
    }
}
