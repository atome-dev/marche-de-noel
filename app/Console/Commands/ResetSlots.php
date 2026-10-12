<?php

namespace App\Console\Commands;

use App\Models\Registration;
use App\Models\Slot;
use App\Models\Stand;
use App\Planning\DefaultSlots;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('marche:reset-slots {--stand= : Identifiant d’un stand, pour ne réinitialiser que celui-là} {--force : Ne pas demander de confirmation (exécution depuis Forge)}')]
#[Description('Efface tous les créneaux (et leurs inscriptions) puis recrée les créneaux par défaut du .env')]
class ResetSlots extends Command
{
    public function handle(DefaultSlots $defaultSlots): int
    {
        $stands = Stand::query()
            ->when($this->option('stand'), fn ($query, $standId) => $query->whereKey($standId))
            ->get();

        if ($stands->isEmpty()) {
            $this->error(__('commands.reset_slots.no_stand'));

            return self::FAILURE;
        }

        if ($defaultSlots->ranges() === [] || config('marche.dates') === []) {
            $this->error(__('commands.reset_slots.no_defaults'));

            return self::FAILURE;
        }

        $slotIds = Slot::query()->whereIn('stand_id', $stands->modelKeys())->pluck('id');

        $this->warn(__('commands.reset_slots.warning', [
            'stands' => $stands->count(),
            'slots' => $slotIds->count(),
            'registrations' => Registration::query()->whereIn('slot_id', $slotIds)->count(),
        ]));

        foreach ($defaultSlots->ranges() as [$startsAt, $endsAt]) {
            $this->line('  '.$startsAt.' – '.$endsAt);
        }

        $word = __('commands.reset_slots.word');

        if (! $this->option('force') && $this->ask(__('commands.reset_slots.confirm', ['word' => $word])) !== $word) {
            $this->line(__('commands.reset_slots.aborted'));

            return self::FAILURE;
        }

        $created = DB::transaction(function () use ($stands, $slotIds, $defaultSlots): int {
            // Registrations and reserved classes go with their slot.
            Slot::query()->whereKey($slotIds)->delete();

            return $stands->sum(fn (Stand $stand) => $defaultSlots->createFor($stand));
        });

        $this->info(__('commands.reset_slots.done', ['count' => $created, 'stands' => $stands->count()]));

        return self::SUCCESS;
    }
}
