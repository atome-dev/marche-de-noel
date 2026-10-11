<?php

namespace App\Livewire\Stands;

use App\Models\SchoolClass;
use App\Models\Slot;
use App\Models\Stand;
use App\Planning\PlanningGrid;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Slots of a stand: day, times, places and optional reservation.
 *
 * @property-read Stand $stand
 * @property-read Collection<int, Slot> $standSlots
 */
class SlotEditor extends Component
{
    #[Locked]
    public int $standId;

    #[Locked]
    public ?int $editingId = null;

    public string $date = '';

    public string $starts_at = '';

    /** Slot length in minutes; the end time is derived from it. */
    public int $duration = 60;

    public int $capacity = 2;

    public string $label = '';

    public bool $is_reserved = false;

    public string $reservedEmails = '';

    /** @var array<int, int|string> */
    public array $reservedClassIds = [];

    public function mount(Stand $stand): void
    {
        $this->authorize('update', $stand);

        $this->standId = $stand->id;
    }

    public function create(): void
    {
        $this->authorize('update', $this->stand);

        $this->resetForm();
        $this->date = config('marche.dates')[0] ?? '';

        Flux::modal('slot-form')->show();
    }

    public function edit(int $slotId): void
    {
        $this->authorize('update', $this->stand);

        $slot = $this->findSlot($slotId);

        $this->resetForm();
        $this->editingId = $slot->id;
        $this->date = $slot->date->toDateString();
        $this->starts_at = $slot->starts_at;
        $this->duration = $slot->durationInMinutes();
        $this->capacity = $slot->capacity;
        $this->label = (string) $slot->label;
        $this->is_reserved = $slot->is_reserved;
        $this->reservedEmails = implode("\n", $slot->reserved_emails ?? []);
        $this->reservedClassIds = $slot->reservedClasses->pluck('id')->all();

        Flux::modal('slot-form')->show();
    }

    public function save(): void
    {
        $this->authorize('update', $this->stand);

        $slot = $this->editingId ? $this->findSlot($this->editingId) : new Slot;

        $validated = $this->validate($this->rules($slot));
        $validated['ends_at'] = $this->endsAt();
        $emails = $this->parsedEmails();

        DB::transaction(function () use ($slot, $validated, $emails): void {
            $slot->fill([
                'date' => $validated['date'],
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'capacity' => $validated['capacity'],
                'label' => $validated['label'] ?: null,
                'is_reserved' => $validated['is_reserved'],
                'reserved_emails' => $emails ?: null,
            ]);
            $slot->stand()->associate($this->stand);
            $slot->save();

            $slot->reservedClasses()->sync($validated['reservedClassIds'] ?? []);
        });

        Flux::modal('slot-form')->close();
        Flux::toast(variant: 'success', text: __('stands.slots.saved'));

        $this->resetForm();
        unset($this->standSlots);
    }

    public function delete(int $slotId): void
    {
        $this->authorize('update', $this->stand);

        $this->findSlot($slotId)->delete();

        Flux::toast(variant: 'success', text: __('stands.slots.deleted'));
        unset($this->standSlots);
    }

    /**
     * Lift the reservation, keeping the list for later.
     */
    public function openToAll(int $slotId): void
    {
        $this->authorize('update', $this->stand);

        $slot = $this->findSlot($slotId);
        $slot->is_reserved = false;
        $slot->save();

        Flux::toast(variant: 'success', text: __('stands.slots.opened'));
        unset($this->standSlots);
    }

    #[Computed]
    public function stand(): Stand
    {
        return Stand::findOrFail($this->standId);
    }

    /**
     * @return Collection<int, Slot>
     */
    #[Computed]
    public function standSlots(): Collection
    {
        return $this->stand->slots()->with('reservedClasses')->withCount('registrations')->get();
    }

    /**
     * @return Collection<int, SchoolClass>
     */
    #[Computed]
    public function schoolClasses(): Collection
    {
        return SchoolClass::query()->ordered()->get();
    }

    public function render(): View
    {
        return view('livewire.stands.slot-editor');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(Slot $slot): array
    {
        $hourMax = config('marche.planning.hour_max');

        return [
            'date' => ['required', Rule::in(config('marche.dates'))],
            'starts_at' => ['required', Rule::in($this->startTimes()), function (string $attribute, mixed $value, \Closure $fail) use ($slot): void {
                if ($conflict = $this->overlappingSlot($slot)) {
                    $fail(__('stands.slots.overlap', ['start' => $conflict->starts_at, 'end' => $conflict->ends_at]));
                }
            }],
            'duration' => ['required', 'integer', Rule::in(config('marche.planning.durations')), function (string $attribute, mixed $value, \Closure $fail) use ($hourMax): void {
                if ($this->endsAt() > $hourMax) {
                    $fail(__('stands.slots.ends_too_late', ['max' => $hourMax]));
                }
            }],
            'capacity' => ['required', 'integer', 'min:'.($slot->exists ? $slot->registrations()->count() : 0), 'max:99'],
            'label' => ['nullable', 'string', 'max:100'],
            'is_reserved' => ['boolean'],
            'reservedEmails' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                $invalid = collect($this->parsedEmails())
                    ->reject(fn (string $email) => Validator::make(['email' => $email], ['email' => 'email'])->passes());

                if ($invalid->isNotEmpty()) {
                    $fail(__('stands.slots.invalid_emails', ['emails' => $invalid->implode(', ')]));
                }
            }],
            'reservedClassIds' => ['array'],
            'reservedClassIds.*' => ['integer', Rule::exists(SchoolClass::class, 'id')],
        ];
    }

    /**
     * Emails separated by new lines, commas, semicolons or spaces.
     *
     * @return list<string>
     */
    private function parsedEmails(): array
    {
        $emails = collect(preg_split('/[\s,;]+/', $this->reservedEmails) ?: [])
            ->map(fn (string $email) => Str::lower(trim($email)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return array_values($emails);
    }

    /**
     * Possible start times: every half hour of the planning hours.
     *
     * @return list<string>
     */
    #[Computed]
    public function startTimes(): array
    {
        $toMinutes = fn (string $time): int => (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);

        $times = [];

        for ($minutes = $toMinutes(config('marche.planning.hour_min')); $minutes < $toMinutes(config('marche.planning.hour_max')); $minutes += PlanningGrid::STEP_MINUTES) {
            $times[] = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
        }

        return $times;
    }

    /**
     * Two slots of the same stand cannot overlap on the same day.
     */
    private function overlappingSlot(Slot $editing): ?Slot
    {
        $endsAt = $this->endsAt();

        if ($endsAt === '' || $this->date === '') {
            return null;
        }

        return $this->stand->slots()
            ->when($editing->exists, fn ($query) => $query->whereKeyNot($editing->id))
            ->whereDate('date', $this->date)
            ->get()
            ->first(fn (Slot $slot) => $slot->starts_at < $endsAt && $this->starts_at < $slot->ends_at);
    }

    /**
     * End time (HH:MM) derived from the start time and the duration.
     */
    private function endsAt(): string
    {
        if (! preg_match('/^\d{2}:\d{2}$/', $this->starts_at)) {
            return '';
        }

        $minutes = (int) substr($this->starts_at, 0, 2) * 60 + (int) substr($this->starts_at, 3, 2) + $this->duration;

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function findSlot(int $slotId): Slot
    {
        return $this->stand->slots()->findOrFail($slotId);
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->reset('editingId', 'date', 'starts_at', 'duration', 'capacity', 'label', 'is_reserved', 'reservedEmails', 'reservedClassIds');
    }
}
