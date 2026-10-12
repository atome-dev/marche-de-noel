<?php

use App\Auth\Invitations;
use App\Livewire\Admin\Users;
use App\Livewire\Auth\AcceptInvitation;
use App\Mail\Invitation;
use App\Models\SchoolClass;
use App\Models\Stand;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    config(['marche.name' => 'Marché de Noël 2026', 'marche.temp_password' => 'Exemple-Commun1']);

    $this->admin = User::factory()->admin()->create();
    $this->candles = Stand::factory()->create(['name' => 'Les petites bougies', 'position' => 1]);
    $this->crepes = Stand::factory()->create(['name' => 'Crêpes', 'position' => 2]);
});

function invite(array $overrides = [])
{
    return Livewire::test(Users::class)
        ->set('inviteFirstName', $overrides['first'] ?? 'Camille')
        ->set('inviteLastName', $overrides['last'] ?? 'Martin')
        ->set('inviteEmail', $overrides['email'] ?? 'Camille@Example.com')
        ->set('inviteStandIds', $overrides['stands'] ?? [])
        ->set('inviteMessage', $overrides['message'] ?? '')
        ->call('invite');
}

describe('sending', function () {
    test('only administrators can invite', function () {
        $this->actingAs(User::factory()->create());

        Livewire::test(Users::class)->assertForbidden();

        expect(User::where('email', 'camille@example.com')->exists())->toBeFalse();
        Mail::assertNothingSent();
    });

    test('the account is created as a stand manager and cannot be used yet', function () {
        $this->actingAs($this->admin);

        invite(['stands' => [$this->candles->id]])->assertHasNoErrors()->assertSee(__('invitations.admin.pending'));

        $user = User::firstWhere('email', 'camille@example.com');

        expect($user->name)->toBe('Camille Martin')
            ->and($user->email_verified_at)->toBeNull()
            ->and($user->isAdmin())->toBeFalse()
            ->and($user->managedStands->pluck('name')->all())->toBe(['Les petites bougies'])
            // Never the shared password: anyone could sign in as the manager.
            ->and(Hash::check('Exemple-Commun1', $user->password))->toBeFalse();

        Mail::assertSent(Invitation::class, fn (Invitation $mail) => $mail->hasTo('camille@example.com'));
    });

    test('the email names the stand and carries the personal message', function () {
        $this->actingAs($this->admin);

        invite(['stands' => [$this->candles->id], 'message' => 'Merci de ton aide !']);

        Mail::assertSent(Invitation::class, function (Invitation $mail) {
            $mail->assertSeeInText('Bonjour Camille,');
            $mail->assertSeeInText('Vous êtes responsable du stand « Les petites bougies » du Marché de Noël 2026.');
            $mail->assertSeeInText('Nous vous invitons à vous inscrire sur le site');
            $mail->assertSeeInText('Merci de ton aide !');
            $mail->assertSeeInHtml('/invitation/');

            return true;
        });
    });

    test('several stands are listed, and none gives a plain invitation', function () {
        $this->actingAs($this->admin);

        invite(['stands' => [$this->crepes->id, $this->candles->id]]);
        invite(['email' => 'dominique@example.com', 'first' => 'Dominique']);

        Mail::assertSent(Invitation::class, function (Invitation $mail) {
            if ($mail->hasTo('camille@example.com')) {
                $mail->assertSeeInText('Vous êtes responsable des stands « Les petites bougies » et « Crêpes » du Marché de Noël 2026.');
            } else {
                $mail->assertSeeInText('Vous êtes invité(e) à rejoindre le site des bénévoles du Marché de Noël 2026.');
                $mail->assertDontSeeInText('responsable');
            }

            return true;
        });
    });

    test('an existing address or an incomplete form is refused', function () {
        $this->actingAs($this->admin);
        User::factory()->create(['email' => 'camille@example.com']);

        invite()->assertHasErrors(['inviteEmail']);
        invite(['email' => 'pas-une-adresse', 'first' => ''])->assertHasErrors(['inviteEmail', 'inviteFirstName']);
        invite(['email' => 'autre@example.com', 'stands' => [999999]])->assertHasErrors(['inviteStandIds.0']);

        expect(User::count())->toBe(2);
        Mail::assertNothingSent();
    });

    test('a pending invitation can be sent again, an active account cannot', function () {
        $this->actingAs($this->admin);
        $pending = app(Invitations::class)->invite('Camille', 'Martin', 'camille@example.com');
        $active = User::factory()->create();

        Livewire::test(Users::class)->call('resendInvitation', $pending->id)->assertOk();
        Livewire::test(Users::class)->call('resendInvitation', $active->id)->assertForbidden();

        Mail::assertSent(Invitation::class, 2);
    });
});

describe('accepting', function () {
    beforeEach(function () {
        $this->invited = app(Invitations::class)->invite('Camille', 'Martin', 'camille@example.com', [$this->candles->id]);
        $this->url = app(Invitations::class)->url($this->invited);
    });

    test('the invited person cannot sign in before accepting', function () {
        $this->post(route('login.store'), ['email' => 'camille@example.com', 'password' => 'Exemple-Commun1']);

        $this->assertGuest();
    });

    test('the link opens the activation form with the profile prefilled', function () {
        $this->get($this->url)
            ->assertOk()
            ->assertSeeLivewire(AcceptInvitation::class)
            ->assertSee('camille@example.com');

        Livewire::test(AcceptInvitation::class, ['user' => $this->invited])
            ->assertSet('first_name', 'Camille')
            ->assertSet('last_name', 'Martin');
    });

    test('a tampered or expired link is refused', function () {
        $this->get(route('invitation.accept', $this->invited))->assertForbidden();
        $this->get(str_replace('/invitation/'.$this->invited->id, '/invitation/'.$this->admin->id, $this->url))->assertForbidden();

        $this->travel(config('marche.invitation_link_ttl') + 1)->minutes();
        $this->get($this->url)->assertForbidden();
    });

    test('accepting sets the password, verifies the address and signs in', function () {
        $class = SchoolClass::query()->first();

        Livewire::test(AcceptInvitation::class, ['user' => $this->invited])
            ->set('first_name', 'Camille-Anne')
            ->set('phone', '06 12 34 56 78')
            ->set('schoolClassIds', [$class->id])
            ->set('password', 'bougie2026')
            ->set('password_confirmation', 'bougie2026')
            ->call('accept')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user = $this->invited->refresh();

        $this->assertAuthenticatedAs($user);

        expect($user->first_name)->toBe('Camille-Anne')
            ->and($user->email_verified_at)->not->toBeNull()
            ->and(Hash::check('bougie2026', $user->password))->toBeTrue()
            ->and($user->schoolClasses->modelKeys())->toBe([$class->id])
            ->and($user->managedStands->modelKeys())->toBe([$this->candles->id]);
    });

    test('the password rules apply', function () {
        Livewire::test(AcceptInvitation::class, ['user' => $this->invited])
            ->set('password', 'abc')
            ->set('password_confirmation', 'abc')
            ->call('accept')
            ->assertHasErrors('password');

        expect($this->invited->refresh()->email_verified_at)->toBeNull();
    });

    test('the link stops working once the account is activated', function () {
        $this->invited->forceFill(['email_verified_at' => now()])->save();

        $this->get($this->url)->assertRedirect(route('login'));
    });

    test('resetting the password through the emailed link activates the account too', function () {
        $token = Password::broker()->createToken($this->invited);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'camille@example.com',
            'password' => 'bougie2026',
            'password_confirmation' => 'bougie2026',
        ])->assertSessionHasNoErrors();

        expect($this->invited->refresh()->email_verified_at)->not->toBeNull();
    });
});
