<?php

namespace App\Console\Commands;

use App\Models\Stand;
use App\Planning\DefaultSlots;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('marche:import-stands')]
#[Description('Crée les stands de la liste de référence (database/data/stands.php) avec leurs créneaux par défaut')]
class ImportStands extends Command
{
    public function handle(DefaultSlots $defaultSlots): int
    {
        $created = 0;
        $position = 0;

        foreach (require database_path('data/stands.php') as $name => $type) {
            $position++;

            // Existing stands are left untouched: managers may have changed them.
            if (Stand::where('name', $name)->exists()) {
                continue;
            }

            $stand = Stand::create([
                'name' => $name,
                'type' => $type,
                'position' => $position,
            ]);

            $defaultSlots->createFor($stand);
            $created++;
        }

        $this->info(trans_choice('commands.import_stands.done', $created, ['count' => $created]));

        return self::SUCCESS;
    }
}
