<?php

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
        // Familles de biscuits : garantissent la diversité des recettes.
        Schema::create('recipe_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->longText('instructions')->nullable();
            $table->string('pdf_url', 500)->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('video_url', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('recipe_school_class', function (Blueprint $table) {
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->primary(['recipe_id', 'school_class_id']);
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('quantity', 20)->nullable();
            $table->string('unit', 30)->nullable();
            // Ingrédient commandable (clé de config) et grammes à commander par kilo produit.
            $table->string('orderable_key', 100)->nullable();
            $table->unsignedInteger('grams_per_kg')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('recipe_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        // Engagement d'une famille : « je prévois de réaliser N kg ».
        Schema::create('commitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('quantity_kg');
            $table->timestamps();

            $table->unique(['user_id', 'recipe_id']);
        });

        Schema::create('ingredient_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commitment_id')->constrained()->cascadeOnDelete();
            $table->string('orderable_key', 100);
            $table->unsignedInteger('grams');
            $table->timestamps();

            $table->unique(['commitment_id', 'orderable_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_orders');
        Schema::dropIfExists('commitments');
        Schema::dropIfExists('recipe_photos');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipe_school_class');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('recipe_categories');
    }
};
