<section class="mx-auto w-full max-w-3xl space-y-8">
    <div>
        <flux:link :href="route('stands.index')" wire:navigate class="text-sm">← {{ __('stands.index.heading') }}</flux:link>
        <flux:heading size="xl" level="1" class="mt-2 font-display">
            {{ $standId ? $name : __('stands.form.create_title') }}
        </flux:heading>
    </div>

    <x-auth-session-status :status="session('status')" />

    <form wire:submit="save" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-4 sm:p-6">
        <flux:input wire:model="name" :label="__('stands.fields.name')" required />

        <flux:textarea wire:model="description" :label="__('stands.fields.description')" rows="3" />

        @if ($this->canManageEverything)
            <div class="grid gap-6 sm:grid-cols-2">
                <flux:select wire:model="type" :label="__('stands.fields.type')">
                    @foreach (\App\Enums\StandType::cases() as $standType)
                        <flux:select.option :value="$standType->value">{{ $standType->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="position" type="number" min="0" :label="__('stands.fields.position')" :description="__('stands.fields.position_help')" />
            </div>

            <flux:select
                wire:model="managerIds"
                variant="listbox"
                multiple
                searchable
                :label="__('stands.fields.managers')"
                :placeholder="__('stands.fields.managers_placeholder')"
            >
                @foreach ($this->users as $user)
                    <flux:select.option :value="$user->id" wire:key="manager-{{ $user->id }}">{{ $user->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:switch wire:model="is_active" :label="__('stands.fields.is_active')" :description="__('stands.fields.is_active_help')" align="left" />
        @endif

        <flux:checkbox.group wire:model="schoolClassIds" :label="__('stands.fields.classes')" :description="__('stands.fields.classes_help')">
            <div class="mt-1 grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-3">
                @foreach ($this->schoolClasses as $schoolClass)
                    <flux:checkbox :value="$schoolClass->id" :label="$schoolClass->name" wire:key="stand-class-{{ $schoolClass->id }}" />
                @endforeach
            </div>
        </flux:checkbox.group>

        <flux:switch wire:model="is_open_to_all" :label="__('stands.fields.is_open_to_all')" :description="__('stands.fields.is_open_to_all_help')" align="left" />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:button type="submit" variant="primary" class="w-full sm:w-auto">{{ __('actions.save') }}</flux:button>

            @if ($standId)
                @can('delete', $this->stand)
                    <flux:button variant="danger" icon="trash" wire:click="delete" wire:confirm="{{ __('stands.form.delete_confirm', ['name' => $name]) }}">
                        {{ __('stands.form.delete') }}
                    </flux:button>
                @endcan
            @endif
        </div>
    </form>

    @if ($standId)
        <livewire:stands.slot-editor :stand="$this->stand" :key="'slots-'.$standId" />
    @endif
</section>
