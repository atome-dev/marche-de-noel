<?php

use Illuminate\Support\Facades\Schedule;

// Instantané des données toutes les 15 minutes (voir App\Snapshots\SnapshotStore).
$snapshot = Schedule::command('marche:snapshot')->everyFifteenMinutes()->withoutOverlapping();

// Surveillance facultative (« heartbeat » de Forge) : l'adresse est appelée après chaque réussite.
if (filled(config('marche.snapshots.heartbeat_url'))) {
    $snapshot->pingOnSuccess(config('marche.snapshots.heartbeat_url'));
}
