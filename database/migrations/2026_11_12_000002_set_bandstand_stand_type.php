<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The reference bandstand stand was imported as a generic stand: its managers
 * now manage the scores. marche:import-stands never updates existing stands.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('stands')
            ->where('name', 'Kiosque à musique')
            ->where('type', 'generic')
            ->update(['type' => 'bandstand']);
    }

    public function down(): void
    {
        DB::table('stands')
            ->where('name', 'Kiosque à musique')
            ->where('type', 'bandstand')
            ->update(['type' => 'generic']);
    }
};
