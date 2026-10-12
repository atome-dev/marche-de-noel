<?php

use Illuminate\Support\Facades\Schedule;

// Instantané des données toutes les 15 minutes (voir App\Snapshots\SnapshotStore).
Schedule::command('marche:snapshot')->everyFifteenMinutes()->withoutOverlapping();
