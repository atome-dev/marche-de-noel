<section class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="lg" class="font-display">{{ __('stands.slots.heading') }}</flux:heading>
            <flux:subheading>
                {{ __('stands.slots.description', ['min' => config('marche.planning.hour_min'), 'max' => config('marche.planning.hour_max')]) }}
            </flux:subheading>
        </div>

        <flux:button icon="plus" wire:click="create" :disabled="blank(config('marche.dates'))">{{ __('stands.slots.add') }}</flux:button>
    </div>

    @if (blank(config('marche.dates')))
        <flux:callout variant="warning" icon="exclamation-triangle" :text="__('stands.slots.no_dates')" />
    @endif

    <div class="divide-y divide-zinc-200 overflow-hidden rounded-xl border border-zinc-200 bg-white">
        @forelse ($this->standSlots as $slot)
            <div class="flex flex-wrap items-center gap-3 p-4" wire:key="slot-{{ $slot->id }}">
                <div class="min-w-0 flex-1">
                    <div class="font-semibold">
                        <span class="inline-block first-letter:uppercase">{{ $slot->date->locale(app()->getLocale())->translatedFormat('l j F') }}</span>
                        <span class="whitespace-nowrap">· {{ $slot->starts_at }}–{{ $slot->ends_at }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-sm text-zinc-600">
                        <span>{{ __('stands.slots.taken', ['taken' => $slot->registrations_count, 'capacity' => $slot->capacity]) }}</span>
                        @if ($slot->label)
                            <flux:badge size="sm">{{ $slot->label }}</flux:badge>
                        @endif
                        @if ($slot->isRestricted())
                            <flux:badge size="sm" color="zinc" icon="lock-closed">{{ __('planning.reserved') }}</flux:badge>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    @if ($slot->isRestricted())
                        <flux:button size="sm" variant="ghost" icon="lock-open" wire:click="openToAll({{ $slot->id }})">
                            {{ __('stands.slots.open_to_all') }}
                        </flux:button>
                    @endif
                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $slot->id }})" :aria-label="__('stands.slots.edit')" />
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $slot->id }})"
                        wire:confirm="{{ $slot->registrations_count ? __('stands.slots.delete_confirm_registered', ['count' => $slot->registrations_count]) : __('stands.slots.delete_confirm') }}"
                        :aria-label="__('actions.delete')" />
                </div>
            </div>
        @empty
            <div class="p-6 text-center text-sm text-zinc-500">{{ __('stands.slots.empty') }}</div>
        @endforelse
    </div>

    <flux:modal name="slot-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">{{ $editingId ? __('stands.slots.edit') : __('stands.slots.add') }}</flux:heading>

            <flux:select wire:model="date" :label="__('stands.slots.date')">
                @foreach (config('marche.dates') as $marketDate)
                    <flux:select.option :value="$marketDate">
                        {{ ucfirst(\Illuminate\Support\Carbon::parse($marketDate)->locale(app()->getLocale())->translatedFormat('l j F')) }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="starts_at" :label="__('stands.slots.starts_at')" :placeholder="__('stands.slots.starts_at_placeholder')">
                    @foreach ($this->startTimes as $time)
                        <flux:select.option :value="$time" wire:key="start-{{ $time }}">{{ $time }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="duration" :label="__('stands.slots.duration')">
                    @foreach (collect(config('marche.planning.durations'))->push($duration)->unique()->sort() as $minutes)
                        <flux:select.option :value="$minutes" wire:key="duration-{{ $minutes }}">{{ sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="capacity" type="number" min="0" max="99" inputmode="numeric" :label="__('stands.slots.capacity')" required />
                <flux:input wire:model="label" :label="__('stands.slots.label')" :placeholder="__('stands.slots.label_placeholder')" />
            </div>

            <flux:switch wire:model.live="is_reserved" :label="__('stands.slots.is_reserved')" :description="__('stands.slots.is_reserved_help')" align="left" />

            @if ($is_reserved)
                <flux:textarea wire:model="reservedEmails" rows="3" :label="__('stands.slots.reserved_emails')" :description="__('stands.slots.reserved_emails_help')" />

                <flux:checkbox.group wire:model="reservedClassIds" :label="__('stands.slots.reserved_classes')">
                    <div class="mt-1 grid grid-cols-2 gap-x-4 gap-y-3">
                        @foreach ($this->schoolClasses as $schoolClass)
                            <flux:checkbox :value="$schoolClass->id" :label="$schoolClass->name" wire:key="slot-class-{{ $schoolClass->id }}" />
                        @endforeach
                    </div>
                </flux:checkbox.group>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('actions.cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('actions.save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
