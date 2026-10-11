<flux:modal name="slot" class="w-full max-w-lg">
    @if ($slot = $this->selectedSlot)
        @php
            $user = auth()->user();
            $canManage = $this->canManageSelected;
            $mine = $slot->hasVolunteer($user);
        @endphp

        <div class="space-y-5" wire:key="slot-modal-{{ $slot->id }}">
            <div>
                <flux:heading size="lg" class="font-display">{{ $slot->stand->name }}</flux:heading>
                <flux:subheading>
                    {{ ucfirst($slot->date->locale(app()->getLocale())->translatedFormat('l j F')) }}
                    · {{ $slot->starts_at }}–{{ $slot->ends_at }}
                    · {{ __('planning.modal.places', ['taken' => $slot->takenCount(), 'capacity' => $slot->capacity]) }}
                </flux:subheading>

                <div class="mt-2 flex flex-wrap gap-1.5">
                    @if ($slot->label)
                        <flux:badge size="sm">{{ $slot->label }}</flux:badge>
                    @endif
                    @if ($slot->isRestricted())
                        <flux:badge size="sm" color="zinc" icon="lock-closed">{{ __('planning.reserved') }}</flux:badge>
                    @endif
                </div>
            </div>

            {{-- Inscrits --}}
            <div>
                <flux:heading size="sm">{{ __('planning.modal.volunteers') }}</flux:heading>
                <ul class="mt-2 divide-y divide-zinc-200 rounded-lg border border-zinc-200">
                    @forelse ($slot->registrations as $registration)
                        <li class="flex items-start justify-between gap-3 px-3 py-2 text-sm" wire:key="registration-{{ $registration->id }}">
                            <div class="min-w-0">
                                <span class="font-semibold">{{ $registration->displayName($canManage) }}</span>
                                @if ($registration->isGuest())
                                    <span class="text-xs text-zinc-500">· {{ __('planning.modal.guest') }}</span>
                                @endif
                                @if ($canManage)
                                    @if ($registration->user?->phone)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $registration->user->phone) }}" class="block text-brand-green hover:underline">{{ $registration->user->phone }}</a>
                                    @endif
                                    @if ($registration->message)
                                        <p class="mt-0.5 text-zinc-600">« {{ $registration->message }} »</p>
                                    @endif
                                @endif
                            </div>
                            @if ($canManage)
                                <flux:button size="xs" variant="ghost" icon="x-mark"
                                    wire:click="removeRegistration({{ $registration->id }})"
                                    wire:confirm="{{ __('planning.modal.remove_confirm', ['name' => $registration->displayName(true)]) }}"
                                    :aria-label="__('planning.modal.remove')" />
                            @endif
                        </li>
                    @empty
                        <li class="px-3 py-3 text-sm text-zinc-500">{{ __('planning.modal.nobody') }}</li>
                    @endforelse
                </ul>
            </div>

            <flux:error name="slot" />

            {{-- Inscription du bénévole --}}
            @if ($mine)
                <flux:callout variant="success" icon="check-circle" :text="__('planning.modal.registered')" />
                <flux:button variant="filled" class="w-full" wire:click="unregister" wire:confirm="{{ __('planning.modal.unregister_confirm') }}">
                    {{ __('planning.modal.unregister') }}
                </flux:button>
            @elseif ($this->refusal)
                <flux:callout variant="secondary" icon="information-circle" :text="__('planning.refusals.'.$this->refusal)" />
            @else
                @if ($this->overlaps->isNotEmpty())
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        <flux:callout.heading>{{ __('planning.modal.overlap_warning') }}</flux:callout.heading>
                        <flux:callout.text>
                            @foreach ($this->overlaps as $overlap)
                                <span class="block">{{ $overlap->stand->name }} · {{ $overlap->starts_at }}–{{ $overlap->ends_at }}</span>
                            @endforeach
                        </flux:callout.text>
                    </flux:callout>
                @endif

                <form wire:submit="register" class="space-y-3">
                    <flux:textarea wire:model="message" rows="2" :label="__('planning.modal.message')" :placeholder="__('planning.modal.message_placeholder')" />
                    <flux:button type="submit" variant="primary" class="w-full">{{ __('planning.modal.register') }}</flux:button>
                </form>
            @endif

            {{-- Actions des responsables --}}
            @if ($canManage)
                <div class="space-y-3 rounded-lg bg-brand-sand p-3">
                    <flux:heading size="sm">{{ __('planning.modal.manager_heading') }}</flux:heading>

                    @unless ($slot->isFull())
                        <form wire:submit="addGuest" class="flex items-end gap-2">
                            <div class="flex-1">
                                <flux:input wire:model="guestName" size="sm" :label="__('planning.modal.guest_name')" :placeholder="__('planning.modal.guest_placeholder')" />
                            </div>
                            <flux:button type="submit" size="sm">{{ __('planning.modal.add_guest') }}</flux:button>
                        </form>
                    @endunless

                    <div class="flex flex-wrap gap-2">
                        @if ($slot->isRestricted())
                            <flux:button size="sm" icon="lock-open" wire:click="openToAll">{{ __('stands.slots.open_to_all') }}</flux:button>
                        @endif
                        <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('stands.edit', $slot->stand)" wire:navigate>
                            {{ __('planning.manage_stand') }}
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    @endif
</flux:modal>
