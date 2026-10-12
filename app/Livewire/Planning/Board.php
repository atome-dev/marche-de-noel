<?php

namespace App\Livewire\Planning;

use App\Exceptions\RegistrationRefused;
use App\Models\Slot;
use App\Models\Stand;
use App\Planning\PlanningFingerprint;
use App\Planning\PlanningGrid;
use App\Planning\SlotRegistrar;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Volunteer planning: stands as rows, slot times as columns.
 *
 * @property-read EloquentCollection<int, Stand> $allStands
 * @property-read PlanningGrid $grid
 * @property-read list<string> $days
 * @property-read Slot|null $selectedSlot
 * @property-read bool $canManageSelected
 * @property-read Collection<int, Slot> $overlaps
 * @property-read string|null $refusal
 */
class Board extends Component
{
    #[Url(except: '')]
    public string $day = '';

    #[Url(as: 'stand', except: null)]
    public ?int $standId = null;

    #[Url(as: 'show', except: 'all')]
    public string $availability = 'all';

    public ?int $selectedSlotId = null;

    public string $message = '';

    public string $guestName = '';

    /**
     * Signature of the planning as it was last drawn.
     */
    #[Locked]
    public string $fingerprint = '';

    /**
     * Called by the periodic poll: redraws only when the planning changed.
     */
    public function refreshIfChanged(PlanningFingerprint $fingerprint): void
    {
        if ($fingerprint->current() === $this->fingerprint) {
            $this->skipRender();
        }
    }

    public function openSlot(int $slotId): void
    {
        $this->selectedSlotId = $slotId;
        $this->reset('message', 'guestName');
        $this->resetValidation();
        unset($this->selectedSlot, $this->overlaps, $this->refusal, $this->canManageSelected);

        abort_if($this->selectedSlot === null, 404);

        Flux::modal('slot')->show();
    }

    public function register(SlotRegistrar $registrar): void
    {
        $slot = $this->selectedSlotOrFail();

        $this->validate(['message' => ['nullable', 'string', 'max:500']]);

        try {
            $registrar->register(Auth::user(), $slot, $this->message);
        } catch (RegistrationRefused $exception) {
            $this->addError('slot', $exception->getMessage());

            return;
        }

        $this->reset('message');
        $this->refreshPlanning();

        Flux::toast(variant: 'success', text: __('planning.toasts.registered'));
    }

    public function unregister(SlotRegistrar $registrar): void
    {
        $registrar->unregister(Auth::user(), $this->selectedSlotOrFail());

        $this->refreshPlanning();

        Flux::toast(text: __('planning.toasts.unregistered'));
    }

    /**
     * A manager fills a place with a person who has no account.
     */
    public function addGuest(SlotRegistrar $registrar): void
    {
        $slot = $this->selectedSlotOrFail();

        $this->authorize('update', $slot->stand);

        $this->validate(['guestName' => ['required', 'string', 'max:100']]);

        try {
            $registrar->addGuest($slot, trim($this->guestName));
        } catch (RegistrationRefused $exception) {
            $this->addError('guestName', $exception->getMessage());

            return;
        }

        $this->reset('guestName');
        $this->refreshPlanning();
    }

    public function removeRegistration(int $registrationId): void
    {
        $slot = $this->selectedSlotOrFail();

        $this->authorize('update', $slot->stand);

        $slot->registrations()->whereKey($registrationId)->delete();

        $this->refreshPlanning();
    }

    public function openToAll(): void
    {
        $slot = $this->selectedSlotOrFail();

        $this->authorize('update', $slot->stand);

        $slot->is_reserved = false;
        $slot->save();

        $this->refreshPlanning();

        Flux::toast(variant: 'success', text: __('stands.slots.opened'));
    }

    /**
     * Every stand shown in the planning, for the stand filter.
     *
     * @return EloquentCollection<int, Stand>
     */
    #[Computed]
    public function allStands(): EloquentCollection
    {
        return Stand::query()
            ->inPlanning()
            ->ordered()
            ->with([
                'schoolClasses',
                'managers',
                'slots' => fn ($slots) => $slots->where('capacity', '>', 0),
                'slots.registrations.user',
                'slots.reservedClasses',
            ])
            ->get()
            ->each(fn (Stand $stand) => $stand->slots->each->setRelation('stand', $stand));
    }

    /**
     * Days offered by the day filter: the market days, and every other day
     * that has slots (preparations). Past days are left out.
     *
     * @return list<string>
     */
    #[Computed]
    public function days(): array
    {
        $days = $this->allStands
            ->flatMap(fn (Stand $stand) => $stand->slots->map(fn (Slot $slot) => $slot->date->toDateString()))
            ->merge(config('marche.dates'))
            ->unique()
            ->filter(fn (string $date): bool => $date >= PlanningGrid::today())
            ->sort();

        return array_values($days->all());
    }

    #[Computed]
    public function grid(): PlanningGrid
    {
        $user = Auth::user()->loadMissing('schoolClasses');
        $registrar = app(SlotRegistrar::class);

        $stands = $this->allStands
            ->when($this->standId, fn ($stands) => $stands->where('id', $this->standId))
            ->values();

        // A stand is kept on a day when one of its slots that day matches the filter.
        $rowFilter = match ($this->availability) {
            'mine' => fn (Stand $stand, Collection $slots): bool => $slots->contains(fn (Slot $slot) => $slot->hasVolunteer($user)),
            'free' => fn (Stand $stand, Collection $slots): bool => $slots->contains(fn (Slot $slot) => $registrar->refusalFor($user, $slot) === null),
            default => null,
        };

        return new PlanningGrid($stands, $this->day ?: null, rowFilter: $rowFilter, from: PlanningGrid::today());
    }

    #[Computed]
    public function selectedSlot(): ?Slot
    {
        if ($this->selectedSlotId === null) {
            return null;
        }

        return Slot::query()
            ->with(['stand.schoolClasses', 'stand.managers', 'registrations.user', 'reservedClasses'])
            ->whereHas('stand', fn ($stand) => $stand->where('is_active', true))
            ->find($this->selectedSlotId);
    }

    /**
     * Managers and admins see full names, phones and messages.
     */
    #[Computed]
    public function canManageSelected(): bool
    {
        return $this->selectedSlot !== null && Gate::allows('update', $this->selectedSlot->stand);
    }

    /**
     * @return Collection<int, Slot>
     */
    #[Computed]
    public function overlaps(): Collection
    {
        if ($this->selectedSlot === null || $this->selectedSlot->hasVolunteer(Auth::user())) {
            return collect();
        }

        return app(SlotRegistrar::class)->overlapsFor(Auth::user(), $this->selectedSlot);
    }

    /**
     * Why the current user cannot register on the selected slot, if any.
     */
    #[Computed]
    public function refusal(): ?string
    {
        if ($this->selectedSlot === null) {
            return null;
        }

        return app(SlotRegistrar::class)->refusalFor(Auth::user(), $this->selectedSlot);
    }

    public function render(): View
    {
        $this->fingerprint = app(PlanningFingerprint::class)->current();

        return view('livewire.planning.board')
            ->title(__('planning.title'));
    }

    private function selectedSlotOrFail(): Slot
    {
        abort_if($this->selectedSlot === null, 404);

        return $this->selectedSlot;
    }

    private function refreshPlanning(): void
    {
        unset($this->allStands, $this->days, $this->grid, $this->selectedSlot, $this->overlaps, $this->refusal, $this->canManageSelected);
    }
}
