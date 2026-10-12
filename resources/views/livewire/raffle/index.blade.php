<section class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:heading size="xl" level="1" class="font-display">{{ __('raffle.title') }}</flux:heading>
        <flux:subheading>{{ __('raffle.description') }}</flux:subheading>
    </div>

    @if ($this->canManage)
        <div class="flex flex-col gap-3 rounded-2xl border border-brand-line bg-white p-4 sm:flex-row sm:items-center">
            <div class="flex-1 space-y-1">
                @if ($this->isValidated)
                    <flux:badge color="green" icon="lock-closed">{{ __('raffle.validated.badge') }}</flux:badge>
                    <p class="text-sm text-zinc-600">{{ __('raffle.validated.help') }}</p>
                @else
                    <flux:badge color="amber" icon="pencil-square">{{ __('raffle.draft.badge') }}</flux:badge>
                    <p class="text-sm text-zinc-600">{{ __('raffle.draft.help') }}</p>
                @endif
            </div>

            @if (! $this->isValidated && $this->prizes->isNotEmpty())
                <flux:button variant="primary" icon="check" wire:click="validateList" wire:confirm="{{ __('raffle.draft.confirm') }}" class="shrink-0">
                    {{ __('raffle.draft.validate') }}
                </flux:button>
            @endif
        </div>

        <form wire:submit="add" class="flex items-end gap-2">
            <div class="flex-1">
                <flux:input wire:model="label" :label="__('raffle.form.new')" :placeholder="__('raffle.form.placeholder')" />
            </div>
            <flux:button type="submit" variant="primary" icon="plus">{{ __('raffle.form.add') }}</flux:button>
        </form>
    @endif

    <div class="divide-y divide-brand-line overflow-hidden rounded-2xl border border-brand-line bg-white"
        @if ($this->canManage && ! $this->isValidated) wire:sort="reorder" @endif>
        @forelse ($this->prizes as $prize)
            @if ($this->canManage)
                <div class="flex flex-wrap items-center gap-2 p-3" wire:key="prize-{{ $prize->id }}-{{ $prize->displayNumber() }}"
                    @if (! $this->isValidated) wire:sort:item="{{ $prize->id }}" @endif
                    x-data="{ label: @js($prize->label), ticket: @js($prize->winning_ticket ?? '') }">
                    @unless ($this->isValidated)
                        <button type="button" wire:sort:handle class="hidden cursor-grab touch-none text-zinc-400 sm:block" aria-label="{{ __('raffle.form.drag') }}">
                            <flux:icon name="bars-3" variant="mini" />
                        </button>
                    @endunless

                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-ink text-sm font-bold text-white"
                        title="{{ __('raffle.number', ['number' => $prize->displayNumber()]) }}">{{ $prize->displayNumber() }}</span>

                    <div class="min-w-0 flex-1 basis-48">
                        <flux:input size="sm" x-model="label" x-on:keydown.enter.prevent="$wire.rename({{ $prize->id }}, label)"
                            x-on:blur="label !== @js($prize->label) && $wire.rename({{ $prize->id }}, label)" :aria-label="__('raffle.form.label')" />
                    </div>

                    <div class="ms-auto flex items-center gap-1">
                        @if ($this->isValidated)
                            <div class="w-32">
                                <flux:input size="sm" icon="ticket" x-model="ticket" :placeholder="__('raffle.ticket_placeholder')" :aria-label="__('raffle.winning_ticket')"
                                    x-on:keydown.enter.prevent="$wire.saveTicket({{ $prize->id }}, ticket)"
                                    x-on:blur="ticket !== @js($prize->winning_ticket ?? '') && $wire.saveTicket({{ $prize->id }}, ticket)" />
                            </div>
                        @else
                            <flux:button size="sm" variant="ghost" icon="chevron-up" wire:click="moveUp({{ $prize->id }})" :disabled="$loop->first" :aria-label="__('raffle.form.move_up')" />
                            <flux:button size="sm" variant="ghost" icon="chevron-down" wire:click="moveDown({{ $prize->id }})" :disabled="$loop->last" :aria-label="__('raffle.form.move_down')" />
                        @endif

                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $prize->id }})"
                            wire:confirm="{{ __('raffle.form.delete_confirm', ['label' => $prize->label]) }}" :aria-label="__('actions.delete')" />
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3 p-3" wire:key="prize-{{ $prize->id }}">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-ink text-sm font-bold text-white"
                        title="{{ __('raffle.number', ['number' => $prize->displayNumber()]) }}">{{ $prize->displayNumber() }}</span>
                    <span class="min-w-0 flex-1">{{ $prize->label }}</span>
                    @if (filled($prize->winning_ticket))
                        <flux:badge color="green" icon="ticket" class="shrink-0">{{ __('raffle.ticket', ['ticket' => $prize->winning_ticket]) }}</flux:badge>
                    @endif
                </div>
            @endif
        @empty
            <div class="p-6 text-center text-sm text-zinc-500">{{ __('raffle.empty') }}</div>
        @endforelse
    </div>
</section>
