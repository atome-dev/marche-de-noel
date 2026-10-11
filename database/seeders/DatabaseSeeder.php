<?php

namespace Database\Seeders;

use App\Enums\StandType;
use App\Models\Registration;
use App\Models\SchoolClass;
use App\Models\Stand;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with development data.
     */
    public function run(): void
    {
        $classes = SchoolClass::query()->pluck('id');

        $admin = User::factory()->admin()->create([
            'first_name' => 'Admin',
            'last_name' => 'Marché',
            'email' => 'admin@example.com',
        ]);
        $admin->schoolClasses()->sync($classes->random(2));

        // Compte administrateur par défaut du développeur, défini dans .env (SEED_ADMIN_*).
        if (filled(config('marche.seed_admin.email')) && filled(config('marche.seed_admin.password'))) {
            User::factory()->admin()->create([
                'first_name' => config('marche.seed_admin.first_name'),
                'last_name' => config('marche.seed_admin.last_name'),
                'email' => config('marche.seed_admin.email'),
                'phone' => null,
                'password' => config('marche.seed_admin.password'),
            ]);
        }

        $manager = User::factory()->create([
            'first_name' => 'Camille',
            'last_name' => 'Responsable',
            'email' => 'responsable@example.com',
        ]);

        $volunteers = User::factory(30)->create()->each(
            fn (User $user) => $user->schoolClasses()->sync($classes->random(random_int(1, 3)))
        );

        $this->seedPlanning($manager, $volunteers->all(), $classes->all());
        Artisan::call('marche:import-recipes');
    }

    /**
     * Real stands list with the default slots, then random registrations.
     *
     * @param  array<int, User>  $volunteers
     * @param  array<int, int>  $classes
     */
    private function seedPlanning(User $manager, array $volunteers, array $classes): void
    {
        Artisan::call('marche:import-stands');

        Stand::query()->ordered()->get()->each(function (Stand $stand, int $index) use ($manager, $volunteers, $classes): void {
            if ($index % 6 === 0 || $stand->type === StandType::Cookies) {
                $stand->managers()->attach($manager);
            }

            if ($index % 7 === 3) {
                $stand->schoolClasses()->attach($classes[$index % count($classes)]);
            }

            foreach ($stand->slots as $slotIndex => $slot) {
                $taken = ($index * 2 + $slotIndex) % ($slot->capacity + 1);

                foreach (array_slice($volunteers, ($index + $slotIndex * 3) % 20, $taken) as $volunteer) {
                    Registration::factory()->for($slot)->for($volunteer)->create();
                }
            }
        });
    }
}
