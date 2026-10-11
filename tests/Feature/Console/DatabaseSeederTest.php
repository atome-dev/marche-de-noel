<?php

use App\Enums\Role;
use App\Models\Recipe;
use App\Models\Stand;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    config([
        'marche.dates' => ['2026-11-28', '2026-11-29'],
        'marche.planning.hour_min' => '09:00',
        'marche.planning.hour_max' => '20:00',
    ]);
});

test('the development seeder fills the database with the reference data', function () {
    config(['marche.seed_admin.email' => null, 'marche.seed_admin.password' => null]);

    $this->seed(DatabaseSeeder::class);

    expect(Stand::count())->toBe(48)
        ->and(Recipe::count())->toBe(21)
        ->and(User::where('email', 'admin@example.com')->exists())->toBeTrue();
});

test('the default administrator comes from the environment', function () {
    config([
        'marche.seed_admin.email' => 'dev@example.org',
        'marche.seed_admin.password' => 'secret-dev-1',
        'marche.seed_admin.first_name' => 'Dev',
        'marche.seed_admin.last_name' => 'Local',
    ]);

    $this->seed(DatabaseSeeder::class);

    $admin = User::firstWhere('email', 'dev@example.org');

    expect($admin->role)->toBe(Role::Admin)
        ->and($admin->name)->toBe('Dev Local')
        ->and(Hash::check('secret-dev-1', $admin->password))->toBeTrue();
});

test('no default administrator is created when it is not configured', function () {
    config(['marche.seed_admin.email' => null, 'marche.seed_admin.password' => null]);

    $this->seed(DatabaseSeeder::class);

    expect(User::where('role', Role::Admin)->pluck('email')->all())->toBe(['admin@example.com']);
});
