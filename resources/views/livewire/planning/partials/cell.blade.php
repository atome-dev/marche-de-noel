{{-- Contenu d'une case du planning. Variables : $slot, $canManage, $compact (liste mobile). --}}
@php
    $status = $slot->statusFor(auth()->user(), $canManage);
    $mine = $slot->hasVolunteer(auth()->user());
@endphp

<button
    type="button"
    wire:click="openSlot({{ $slot->id }})"
    title="{{ $slot->starts_at }}–{{ $slot->ends_at }}{{ $slot->label ? ' · '.$slot->label : '' }}"
    @if ($status === 'reserved') data-slot-reserved @endif
    data-status="{{ $status }}"
    @class([
        'planning-cell flex w-full flex-col gap-0.5 overflow-hidden rounded-md border-l-4 text-left transition hover:brightness-95 focus-visible:outline-2 focus-visible:outline-brand-ink',
        'h-full min-h-16 px-1 py-1' => ! $compact,
        'px-2 py-1.5' => $compact,
        'border-red-400 bg-red-50' => $status === 'empty',
        'border-amber-400 bg-amber-50' => $status === 'partial',
        'border-emerald-500 bg-emerald-50' => $status === 'full',
        'border-zinc-400 bg-zinc-100 text-zinc-500' => $status === 'reserved',
        'ring-2 ring-brand-ink' => $mine,
    ])
>
    @if ($status === 'reserved')
        <span class="flex items-center gap-1 text-xs font-semibold">
            <flux:icon name="lock-closed" variant="micro" class="shrink-0" /> <span class="truncate">{{ __('planning.reserved') }}</span>
        </span>
    @else
        <span class="flex items-center gap-1 whitespace-nowrap text-xs font-bold">
            {{ $slot->takenCount() }}/{{ $slot->capacity }}
            @if ($slot->isRestricted())
                <flux:icon name="lock-closed" variant="micro" class="text-zinc-500" />
            @endif
        </span>
        <span class="text-xs leading-tight text-zinc-700">
            @forelse ($slot->registrations as $registration)
                <span @class(['block truncate', 'font-semibold text-brand-ink' => $registration->user_id === auth()->id()])>{{ $registration->displayName() }}</span>
            @empty
                <span class="block truncate text-red-700">{{ __('planning.to_fill') }}</span>
            @endforelse
        </span>
    @endif
</button>
