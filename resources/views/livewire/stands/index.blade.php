<section class="mx-auto w-full max-w-4xl space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1" class="font-display">{{ __('stands.index.heading') }}</flux:heading>
            <flux:subheading>
                {{ auth()->user()->isAdmin() ? __('stands.index.admin_description') : __('stands.index.manager_description') }}
            </flux:subheading>
        </div>

        @can('create', \App\Models\Stand::class)
            <flux:button variant="primary" icon="plus" :href="route('stands.create')" wire:navigate>
                {{ __('stands.index.create') }}
            </flux:button>
        @endcan
    </div>

    <x-auth-session-status :status="session('status')" />

    @can('admin')
        <p class="text-sm text-zinc-600">{{ __('stands.index.sort_help') }}</p>
    @endcan

    <div class="divide-y divide-zinc-200 overflow-hidden rounded-xl border border-zinc-200 bg-white" @can('admin') wire:sort="reorder" @endcan>
        @forelse ($this->stands as $stand)
            <div class="flex items-stretch bg-white" wire:key="stand-{{ $stand->id }}" @can('admin') wire:sort:item="{{ $stand->id }}" @endcan>
            @can('admin')
                <button type="button" wire:sort:handle class="flex w-10 shrink-0 cursor-grab touch-none items-center justify-center text-zinc-400 hover:text-zinc-600" aria-label="{{ __('stands.index.drag') }}">
                    <flux:icon name="bars-3" variant="mini" />
                </button>
            @endcan
            <a href="{{ route('stands.edit', $stand) }}" wire:navigate
               @class(['flex min-w-0 flex-1 items-center gap-4 p-4 hover:bg-zinc-50', 'ps-1' => auth()->user()->isAdmin()])>
                <div class="min-w-0 flex-1 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold">{{ $stand->name }}</span>
                        @if ($stand->type !== \App\Enums\StandType::Generic)
                            <flux:badge size="sm" color="teal">{{ $stand->type->label() }}</flux:badge>
                        @endif
                        @unless ($stand->is_active)
                            <flux:badge size="sm" color="zinc">{{ __('stands.inactive') }}</flux:badge>
                        @endunless
                    </div>
                    <div class="text-sm text-zinc-600">
                        {{ trans_choice('stands.index.slots_count', $stand->slots_count, ['count' => $stand->slots_count]) }}
                        · {{ trans_choice('stands.index.places_count', (int) $stand->slots_sum_capacity, ['count' => (int) $stand->slots_sum_capacity]) }}
                        @if ($stand->managers->isNotEmpty())
                            · {{ $stand->managers->map->shortName()->implode(', ') }}
                        @endif
                    </div>
                    @if ($stand->schoolClasses->isNotEmpty())
                        <div class="text-xs text-zinc-500">
                            {{ $stand->schoolClasses->pluck('name')->implode(', ') }}
                            @if ($stand->is_open_to_all) · {{ __('stands.open_to_all') }} @endif
                        </div>
                    @endif
                </div>
                <flux:icon name="chevron-right" variant="micro" class="text-zinc-400" />
            </a>
            </div>
        @empty
            <div class="p-8 text-center text-zinc-500">{{ __('stands.index.empty') }}</div>
        @endforelse
    </div>
</section>
