<?php

use App\Models\Commitment;
use App\Models\IngredientOrder;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\RecipeIngredient;
use App\Models\Registration;
use App\Models\SchoolClass;
use App\Models\Slot;
use App\Models\Stand;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    $this->user->schoolClasses()->attach(SchoolClass::query()->first());

    $this->stand = Stand::factory()->create();
    $this->stand->managers()->attach($this->user);
    $this->stand->schoolClasses()->attach(SchoolClass::query()->first());
    $slot = Slot::factory()->for($this->stand)->reservedFor(['camille@example.com'])->create();
    $slot->reservedClasses()->attach(SchoolClass::query()->first());
    Registration::factory()->for($slot)->for($this->user)->create();
    Registration::factory()->for($slot)->guest('Mamie Jeanne')->create();

    DB::table('password_reset_tokens')->insert([
        'email' => $this->user->email,
        'token' => 'token',
        'created_at' => now(),
    ]);
});

test('nothing is deleted without the exact confirmation word', function () {
    $this->artisan('marche:purge')
        ->expectsQuestion(__('commands.purge.confirm', ['word' => __('commands.purge.word')]), 'oui')
        ->expectsOutput(__('commands.purge.aborted'))
        ->assertFailed();

    expect(User::count())->toBe(1);
    $this->assertDatabaseCount('password_reset_tokens', 1);
});

test('every personal data is deleted after confirmation', function () {
    $classCount = SchoolClass::count();

    $this->artisan('marche:purge')
        ->expectsQuestion(__('commands.purge.confirm', ['word' => __('commands.purge.word')]), __('commands.purge.word'))
        ->assertSuccessful();

    expect(User::count())->toBe(0)
        ->and(SchoolClass::count())->toBe($classCount);

    $this->assertDatabaseCount('school_class_user', 0);
    $this->assertDatabaseCount('password_reset_tokens', 0);
    $this->assertDatabaseCount('sessions', 0);
});

test('slots, registrations and managers are deleted but stands are kept', function () {
    $this->artisan('marche:purge')
        ->expectsQuestion(__('commands.purge.confirm', ['word' => __('commands.purge.word')]), __('commands.purge.word'))
        ->assertSuccessful();

    $this->assertDatabaseCount('registrations', 0);
    $this->assertDatabaseCount('slots', 0);
    $this->assertDatabaseCount('school_class_slot', 0);
    $this->assertDatabaseCount('stand_manager', 0);

    expect(Stand::count())->toBe(1)
        ->and($this->stand->fresh()->schoolClasses)->toHaveCount(1);
});

test('commitments and orders are deleted but recipes are kept', function () {
    $recipe = Recipe::factory()->create();
    RecipeIngredient::factory()->for($recipe)->create();
    IngredientOrder::factory()->for(Commitment::factory()->for($recipe)->for($this->user))->create();

    $this->artisan('marche:purge')
        ->expectsQuestion(__('commands.purge.confirm', ['word' => __('commands.purge.word')]), __('commands.purge.word'))
        ->assertSuccessful();

    $this->assertDatabaseCount('commitments', 0);
    $this->assertDatabaseCount('ingredient_orders', 0);

    expect(Recipe::count())->toBe(1)
        ->and(RecipeCategory::count())->toBe(1)
        ->and($recipe->ingredients()->count())->toBe(1);
});
