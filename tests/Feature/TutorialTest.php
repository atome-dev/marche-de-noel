<?php

use App\Http\Controllers\Help\ShowTutorial;
use App\Models\User;

test('every step of every guide has a text and a screen', function (string $guide) {
    $steps = __('tutorial.guides.'.$guide.'.steps');

    expect(array_keys($steps))->toBe(array_keys(ShowTutorial::GUIDES[$guide]['screens']));

    foreach ($steps as $key => $step) {
        expect(public_path('images/tutoriel/'.$guide.'/'.$key.'.webp'))->toBeFile()
            ->and($step['title'])->not->toBeEmpty()
            ->and($step['text'])->not->toBeEmpty();
    }
})->with(array_keys(ShowTutorial::GUIDES));

test('guests read the volunteer guide only', function () {
    $response = $this->get(route('tutorial'))->assertOk()->assertSee(__('tutorial.title'));

    foreach (__('tutorial.guides.benevoles.steps') as $key => $step) {
        $response->assertSee($step['title'])->assertSee('images/tutoriel/benevoles/'.$key.'.webp');
    }

    $response->assertSee(route('register'))
        ->assertDontSee(__('tutorial.cta_planning'))
        ->assertDontSee(__('tutorial.guides.responsables.name'));

    $this->get(route('tutorial', 'responsables'))->assertRedirect(route('login'));
    $this->get(route('tutorial', 'administrateurs'))->assertRedirect(route('login'));
    $this->get(route('tutorial', 'inconnu'))->assertNotFound();
});

test('signed-in volunteers also get the stand manager guide', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('tutorial'))
        ->assertOk()
        ->assertSee(__('tutorial.cta_planning'))
        ->assertSee(route('tutorial', 'responsables'))
        ->assertDontSee(route('tutorial', 'administrateurs'));

    $this->get(route('tutorial', 'responsables'))
        ->assertOk()
        ->assertSee(__('tutorial.guides.responsables.steps.creneaux.title'))
        ->assertSee('images/tutoriel/responsables/creneaux.webp')
        ->assertSee(__('tutorial.cta_stands'));

    $this->get(route('tutorial', 'administrateurs'))->assertForbidden();
});

test('administrators get the three guides', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('tutorial', 'administrateurs'))
        ->assertOk()
        ->assertSee(__('tutorial.guides.administrateurs.steps.sauvegardes.title'))
        ->assertSee('images/tutoriel/administrateurs/sauvegardes.webp')
        ->assertSee(route('tutorial', 'responsables'))
        ->assertSee(__('tutorial.cta_users'));
});

test('the tutorial is linked from the login page, the dashboard and the menu', function () {
    $this->get(route('login'))->assertSee(route('tutorial'));

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertSee(__('dashboard.tutorial'))
        ->assertSee(__('tutorial.link'));
});
