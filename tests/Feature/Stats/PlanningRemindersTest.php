<?php

use App\Livewire\Stats\Index;
use App\Mail\StandPlanningReminder;
use App\Models\Registration;
use App\Models\Slot;
use App\Models\Stand;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo('2026-11-22 10:00:00');
    Mail::fake();

    $this->camille = User::factory()->create(['first_name' => 'Camille', 'last_name' => 'Martin']);
    $this->dominique = User::factory()->create(['first_name' => 'Dominique', 'last_name' => 'Arnaud']);

    // Incomplete, two managers.
    $this->buvette = Stand::factory()->create(['name' => 'Buvette', 'position' => 1]);
    $this->buvette->managers()->attach([$this->camille->id, $this->dominique->id]);
    $slot = Slot::factory()->for($this->buvette)->at('2026-11-28', '10:00', '12:00')->create(['capacity' => 2]);
    Registration::factory()->for($slot)->for(User::factory()->state(['first_name' => 'Alix', 'last_name' => 'Durand']))->create();
    Slot::factory()->for($this->buvette)->at('2026-11-20', '10:00', '12:00')->create(['capacity' => 3]); // past

    // Complete.
    $this->crepes = Stand::factory()->create(['name' => 'Crêpes', 'position' => 2]);
    $this->crepes->managers()->attach($this->camille);
    $full = Slot::factory()->for($this->crepes)->at('2026-11-28', '10:00', '12:00')->create(['capacity' => 1]);
    Registration::factory()->for($full)->create();

    // Incomplete, without manager.
    $this->vin = Stand::factory()->create(['name' => 'Vin chaud', 'position' => 3]);
    Slot::factory()->for($this->vin)->at('2026-11-28', '10:00', '12:00')->create();

    $this->admin = User::factory()->admin()->create();
});

test('the table lists the managers of each stand', function () {
    $this->actingAs($this->admin);

    Livewire::test(Index::class)
        ->assertSee('Dominique Arnaud, Camille Martin')
        ->assertSee('Aucun responsable')
        ->assertSee('Relancer les responsables des stands incomplets');
});

test('the global button emails each manager of every incomplete stand', function () {
    $this->actingAs($this->admin);

    Livewire::test(Index::class)->call('sendReminders');

    Mail::assertSentCount(2);
    Mail::assertSent(StandPlanningReminder::class, fn (StandPlanningReminder $mail) => $mail->hasTo($this->camille->email) && $mail->stand->is($this->buvette));
    Mail::assertSent(StandPlanningReminder::class, fn (StandPlanningReminder $mail) => $mail->hasTo($this->dominique->email) && $mail->stand->is($this->buvette));
});

test('the stand button only reminds the managers of that stand', function () {
    $this->actingAs($this->admin);

    Livewire::test(Index::class)->call('sendReminders', $this->buvette->id);
    Mail::assertSentCount(2);

    // A complete stand is not reminded.
    Livewire::test(Index::class)->call('sendReminders', $this->crepes->id);
    Mail::assertSentCount(2);
});

test('the email greets the manager and shows the current planning of the stand', function () {
    config(['marche.name' => 'Marché de Noël']);

    $mail = new StandPlanningReminder($this->camille, $this->buvette);

    $mail->assertHasSubject('Marché de Noël — le planning du stand « Buvette » n’est pas complet');
    $mail->assertSeeInHtml('Bonjour Camille Martin,', false);
    $mail->assertSeeInHtml('le planning de votre stand « Buvette » n’est pas complet, merci de relancer les parents de votre classe.', false);
    $mail->assertSeeInHtml('10:00–12:00', false);
    $mail->assertSeeInHtml('1 / 2', false);
    $mail->assertSeeInHtml('Alix Durand', false);
    $mail->assertDontSeeInHtml('1 / 3', false);
});

test('every day of the planning is a heading in the email', function () {
    Slot::factory()->for($this->buvette)->at('2026-11-29', '14:00', '16:00')->create();

    $html = (new StandPlanningReminder($this->camille, $this->buvette))->render();

    expect($html)->not->toContain('##')
        ->and(substr_count($html, '<h2'))->toBe(2);
});

test('managers cannot send reminders', function () {
    $this->actingAs($this->camille);

    Livewire::test(Index::class)
        ->assertSee('Camille Martin')
        ->assertDontSee('Relancer')
        ->call('sendReminders')
        ->assertForbidden();

    Mail::assertNothingSent();
});
