<?php

namespace App\Providers;

use App\Enums\StandType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureUploads();

        // Resetting a password through the emailed link proves the address:
        // an invited person who used "forgot password" is activated too.
        Event::listen(function (PasswordReset $event): void {
            if ($event->user instanceof User && $event->user->email_verified_at === null) {
                $event->user->forceFill(['email_verified_at' => now()])->save();
            }
        });
    }

    /**
     * Livewire refuses temporary uploads above 12 MB by default: follow the
     * documents limit, never going under that default.
     */
    protected function configureUploads(): void
    {
        $maxKilobytes = max(12288, (int) config('marche.documents.max_size_mb') * 1024);

        config(['livewire.temporary_file_upload.rules' => ['required', 'file', 'max:'.$maxKilobytes]]);
    }

    /**
     * Administrators have every right, everywhere.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(fn (User $user): ?bool => $user->isAdmin() ? true : null);

        Gate::define('admin', fn (User $user): bool => $user->isAdmin());

        // Recipes, biscuit families and the purchasing summary.
        Gate::define('manage-cookies', fn (User $user): bool => $user->managedStands()
            ->where('type', StandType::Cookies)
            ->exists());

        // Raffle prizes and winning tickets.
        Gate::define('manage-raffle', fn (User $user): bool => $user->managedStands()
            ->where('type', StandType::Raffle)
            ->exists());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Site privé et temporaire : exigences volontairement modestes.
        Password::defaults(fn (): Password => Password::min(6)
            ->letters()
            ->numbers());
    }
}
