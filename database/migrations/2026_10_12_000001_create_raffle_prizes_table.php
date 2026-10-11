<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raffle_prizes', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            // Rank in the list: 1 is the most important prize.
            $table->unsignedInteger('position');
            // Frozen number, set when the list is validated.
            $table->unsignedInteger('number')->nullable()->unique();
            $table->string('winning_ticket', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raffle_prizes');
    }
};
