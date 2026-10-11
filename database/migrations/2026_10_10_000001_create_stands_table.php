<?php

use App\Enums\StandType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 20)->default(StandType::Generic->value);
            $table->boolean('is_active')->default(true);
            // Lève la restriction aux classes associées.
            $table->boolean('is_open_to_all')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('stand_manager', function (Blueprint $table) {
            $table->foreignId('stand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['stand_id', 'user_id']);
        });

        Schema::create('school_class_stand', function (Blueprint $table) {
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stand_id')->constrained()->cascadeOnDelete();
            $table->primary(['school_class_id', 'stand_id']);
        });

        Schema::create('slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stand_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('capacity')->default(1);
            // Libellé indicatif, par exemple « Montage ».
            $table->string('label', 100)->nullable();
            $table->boolean('is_reserved')->default(false);
            $table->json('reserved_emails')->nullable();
            $table->timestamps();

            $table->index(['date', 'starts_at', 'ends_at']);
        });

        Schema::create('school_class_slot', function (Blueprint $table) {
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('slot_id')->constrained()->cascadeOnDelete();
            $table->primary(['school_class_id', 'slot_id']);
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained()->cascadeOnDelete();
            // Null quand le responsable inscrit une personne sans compte.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('guest_name', 100)->nullable();
            $table->string('message', 500)->nullable();
            $table->timestamps();

            $table->unique(['slot_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('school_class_slot');
        Schema::dropIfExists('slots');
        Schema::dropIfExists('school_class_stand');
        Schema::dropIfExists('stand_manager');
        Schema::dropIfExists('stands');
    }
};
