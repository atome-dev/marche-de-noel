<?php

use App\Http\Controllers\Settings\ConfirmEmailChange;
use App\Http\Middleware\ForbidWhileImpersonating;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Security;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', ForbidWhileImpersonating::class])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', Profile::class)->name('profile.edit');
    Route::livewire('settings/password', Security::class)->name('security.edit');
});

Route::get('settings/email/confirm/{user}', ConfirmEmailChange::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('profile.email.confirm');
