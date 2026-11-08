<?php

use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->app->maintenanceMode()->activate([]);
});

afterEach(function () {
    $this->app->maintenanceMode()->deactivate();
});

test('the maintenance page explains the pause to everyone', function () {
    $this->get(route('login'))
        ->assertStatus(503)
        ->assertSee(__('errors.maintenance.title'))
        ->assertSee(__('errors.maintenance.text'))
        ->assertSee(config('marche.name'));
});

test('the maintenance page announces the return time set in the banner', function () {
    config(['marche.timezone' => 'Europe/Paris']);
    $this->travelTo(Carbon::parse('2026-11-21 07:00:00', 'UTC'));
    config(['marche.info_banner' => ['message' => 'Maintenance', 'start' => null, 'end' => '2026-11-21 10:30']]);

    $this->get(route('login'))
        ->assertStatus(503)
        ->assertSee(__('errors.maintenance.back_at', ['date' => 'samedi 21 novembre', 'time' => '10h30']));
});

test('the maintenance page announces no return time once the banner end has passed', function () {
    config(['marche.info_banner' => ['message' => null, 'start' => null, 'end' => now(config('marche.timezone'))->subHour()->format('Y-m-d H:i')]]);

    $this->get(route('login'))
        ->assertStatus(503)
        ->assertSee(__('errors.maintenance.back_soon'))
        ->assertDontSee('Retour prévu');
});
