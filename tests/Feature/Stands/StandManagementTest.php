<?php

use App\Enums\StandType;
use App\Livewire\Stands\Index;
use App\Livewire\Stands\StandForm;
use App\Models\SchoolClass;
use App\Models\Stand;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->manager = User::factory()->create();
    $this->stand = Stand::factory()->create(['name' => 'Buvette']);
    $this->stand->managers()->attach($this->manager);
});

describe('access', function () {
    test('guests are redirected to login', function () {
        $this->get(route('stands.index'))->assertRedirect(route('login'));
    });

    test('admins see every stand', function () {
        Stand::factory()->create(['name' => 'Crêpes']);

        $this->actingAs($this->admin)
            ->get(route('stands.index'))
            ->assertOk()
            ->assertSee('Buvette')
            ->assertSee('Crêpes');
    });

    test('managers only see their stands', function () {
        Stand::factory()->create(['name' => 'Crêpes']);

        $this->actingAs($this->manager);

        Livewire::test(Index::class)
            ->assertSee('Buvette')
            ->assertDontSee('Crêpes');
    });

    test('managers can edit their stand', function () {
        $this->actingAs($this->manager)
            ->get(route('stands.edit', $this->stand))
            ->assertOk();
    });

    test('volunteers cannot edit a stand they do not manage', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('stands.edit', $this->stand))
            ->assertForbidden();
    });

    test('only admins can create stands', function () {
        $this->actingAs($this->manager)->get(route('stands.create'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('stands.create'))->assertOk();
    });
});

describe('stand form', function () {
    test('admins create a stand with type, managers and classes', function () {
        $classes = SchoolClass::query()->ordered()->take(2)->pluck('id')->all();

        $this->actingAs($this->admin);

        Livewire::test(StandForm::class)
            ->set('name', 'Petits gâteaux')
            ->set('description', 'Vente des biscuits')
            ->set('type', StandType::Cookies->value)
            ->set('managerIds', [$this->manager->id])
            ->set('schoolClassIds', $classes)
            ->call('save')
            ->assertHasNoErrors();

        $stand = Stand::firstWhere('name', 'Petits gâteaux');

        expect($stand->type)->toBe(StandType::Cookies)
            ->and($stand->is_active)->toBeTrue()
            ->and($stand->managers->pluck('id')->all())->toBe([$this->manager->id])
            ->and($stand->schoolClasses->pluck('id')->all())->toEqualCanonicalizing($classes);
    });

    test('managers update their stand but not the admin-only fields', function () {
        $other = User::factory()->create();
        $class = SchoolClass::query()->first();

        $this->actingAs($this->manager);

        Livewire::test(StandForm::class, ['stand' => $this->stand])
            ->set('name', 'Buvette du parc')
            ->set('schoolClassIds', [$class->id])
            ->set('is_open_to_all', true)
            ->set('type', StandType::Raffle->value)
            ->set('is_active', false)
            ->set('managerIds', [$other->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->stand->refresh();

        expect($this->stand->name)->toBe('Buvette du parc')
            ->and($this->stand->is_open_to_all)->toBeTrue()
            ->and($this->stand->schoolClasses->pluck('id')->all())->toBe([$class->id])
            ->and($this->stand->type)->toBe(StandType::Generic)
            ->and($this->stand->is_active)->toBeTrue()
            ->and($this->stand->managers->pluck('id')->all())->toBe([$this->manager->id]);
    });

    test('the name is required', function () {
        $this->actingAs($this->admin);

        Livewire::test(StandForm::class)
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    });

    test('admins can deactivate and delete a stand', function () {
        $this->actingAs($this->admin);

        Livewire::test(StandForm::class, ['stand' => $this->stand])
            ->set('is_active', false)
            ->call('save');

        expect($this->stand->refresh()->is_active)->toBeFalse();

        Livewire::test(StandForm::class, ['stand' => $this->stand])
            ->call('delete')
            ->assertRedirect(route('stands.index'));

        expect($this->stand->fresh())->toBeNull();
    });

    test('managers cannot delete their stand', function () {
        $this->actingAs($this->manager);

        Livewire::test(StandForm::class, ['stand' => $this->stand])
            ->call('delete')
            ->assertForbidden();
    });
});
