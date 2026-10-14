<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The reference tree stand was imported as a generic stand: it now unlocks
 * the tree orders. marche:import-stands never updates existing stands.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('stands')
            ->where('name', 'Sapins du Morvan')
            ->where('type', 'generic')
            ->update(['type' => 'tree_order']);
    }

    public function down(): void
    {
        DB::table('stands')
            ->where('name', 'Sapins du Morvan')
            ->where('type', 'tree_order')
            ->update(['type' => 'generic']);
    }
};
