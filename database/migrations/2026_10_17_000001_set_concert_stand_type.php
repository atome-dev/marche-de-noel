<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The reference concert stand was imported as a generic stand: it now unlocks
 * the ticket follow-up. marche:import-stands never updates existing stands.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('stands')
            ->where('name', "Concert du Grand Orchestre de l'école")
            ->where('type', 'generic')
            ->update(['type' => 'concert']);
    }

    public function down(): void
    {
        DB::table('stands')
            ->where('name', "Concert du Grand Orchestre de l'école")
            ->where('type', 'concert')
            ->update(['type' => 'generic']);
    }
};
