<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('school_class_user', function (Blueprint $table) {
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['school_class_id', 'user_id']);
        });

        // Données de référence : conservées lors de la purge annuelle.
        $names = ['JE Pommier', 'JE Parc', 'JE Balcon', '1ère Classe'];

        foreach (range(2, 13) as $grade) {
            $names[] = $grade.'ème Classe';
        }

        $now = now();

        DB::table('school_classes')->insert(array_map(
            fn (string $name, int $index): array => [
                'name' => $name,
                'position' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $names,
            array_keys($names),
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_class_user');
        Schema::dropIfExists('school_classes');
    }
};
