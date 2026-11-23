<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Last email reminder sent to the managers: reminders are spaced out.
     */
    public function up(): void
    {
        Schema::table('stands', function (Blueprint $table) {
            $table->timestamp('planning_reminded_at')->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('stands', function (Blueprint $table) {
            $table->dropColumn('planning_reminded_at');
        });
    }
};
