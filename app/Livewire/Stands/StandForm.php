<?php

namespace App\Livewire\Stands;

use App\Enums\StandType;
use App\Models\SchoolClass;
use App\Models\Stand;
use App\Models\User;
use App\Planning\DefaultSlots;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read Stand|null $stand
 */
class StandForm extends Component
{
    #[Locked]
    public ?int $standId = null;

    public string $name = '';

    public string $description = '';

    public string $type = '';

    public bool $is_active = true;

    public bool $is_open_to_all = false;

    public int $position = 0;

    /** @var array<int, int|string> */
    public array $managerIds = [];

    /** @var array<int, int|string> */
    public array $schoolClassIds = [];

    public function mount(?Stand $stand = null): void
    {
        $this->type = StandType::Generic->value;

        if ($stand === null || ! $stand->exists) {
            $this->authorize('create', Stand::class);

            return;
        }

        $this->authorize('update', $stand);

        $this->standId = $stand->id;
        $this->name = $stand->name;
        $this->description = (string) $stand->description;
        $this->type = $stand->type->value;
        $this->is_active = $stand->is_active;
        $this->is_open_to_all = $stand->is_open_to_all;
        $this->position = $stand->position;
        $this->managerIds = $stand->managers->pluck('id')->all();
        $this->schoolClassIds = $stand->schoolClasses->pluck('id')->all();
    }

    public function save(): void
    {
        $stand = $this->stand ?? new Stand;

        $this->authorize($stand->exists ? 'update' : 'create', $stand->exists ? $stand : Stand::class);

        $validated = $this->validate($this->rules());

        $isNew = ! $stand->exists;

        DB::transaction(function () use ($stand, $validated): void {
            $stand->fill([
                'name' => $validated['name'],
                'description' => $validated['description'] ?: null,
                'is_open_to_all' => $validated['is_open_to_all'],
            ]);

            if ($this->canManageEverything()) {
                $stand->fill([
                    'type' => $validated['type'],
                    'is_active' => $validated['is_active'],
                    'position' => $validated['position'],
                ]);
            }

            $stand->save();

            $stand->schoolClasses()->sync($validated['schoolClassIds'] ?? []);

            if ($this->canManageEverything()) {
                $stand->managers()->sync($validated['managerIds'] ?? []);
            }
        });

        if ($isNew) {
            app(DefaultSlots::class)->createFor($stand);

            session()->flash('status', __('stands.form.created'));

            $this->redirectRoute('stands.edit', $stand, navigate: true);

            return;
        }

        unset($this->stand);

        Flux::toast(variant: 'success', text: __('stands.form.saved'));
    }

    public function delete(): void
    {
        $stand = $this->stand;

        abort_if($stand === null, 404);

        $this->authorize('delete', $stand);

        $stand->delete();

        session()->flash('status', __('stands.form.deleted', ['name' => $stand->name]));

        $this->redirectRoute('stands.index', navigate: true);
    }

    #[Computed]
    public function stand(): ?Stand
    {
        return $this->standId ? Stand::findOrFail($this->standId) : null;
    }

    /**
     * Type, activation, order and managers are set by administrators only.
     */
    #[Computed]
    public function canManageEverything(): bool
    {
        return Auth::user()->isAdmin();
    }

    /**
     * @return Collection<int, SchoolClass>
     */
    #[Computed]
    public function schoolClasses(): Collection
    {
        return SchoolClass::query()->ordered()->get();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function users(): Collection
    {
        return User::query()->orderBy('last_name')->orderBy('first_name')->get();
    }

    public function render(): View
    {
        return view('livewire.stands.stand-form')
            ->title($this->standId ? $this->name : __('stands.form.create_title'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::enum(StandType::class)],
            'is_active' => ['boolean'],
            'is_open_to_all' => ['boolean'],
            'position' => ['integer', 'min:0', 'max:999'],
            'managerIds' => ['array'],
            'managerIds.*' => ['integer', Rule::exists(User::class, 'id')],
            'schoolClassIds' => ['array'],
            'schoolClassIds.*' => ['integer', Rule::exists(SchoolClass::class, 'id')],
        ];
    }
}
