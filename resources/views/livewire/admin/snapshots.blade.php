<section class="mx-auto w-full max-w-3xl space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0 flex-1 basis-72">
            <flux:heading size="xl" level="1" class="font-display">{{ __('admin.snapshots.heading') }}</flux:heading>
            <flux:subheading>{{ __('admin.snapshots.description', ['days' => config('marche.snapshots.retention_days')]) }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="circle-stack" wire:click="create">{{ __('admin.snapshots.create') }}</flux:button>
    </div>

    @if (count($this->snapshots) > 0)
        <p class="text-sm text-zinc-600">
            {{ trans_choice('admin.snapshots.count', count($this->snapshots), ['count' => count($this->snapshots)]) }}
            @if (count($this->snapshots) > self::LISTED)
                · {{ __('admin.snapshots.more', ['shown' => self::LISTED]) }}
            @endif
        </p>
    @endif

    <div class="divide-y divide-brand-line overflow-hidden rounded-2xl border border-brand-line bg-white">
        @forelse (array_slice($this->snapshots, 0, self::LISTED) as $snapshot)
            @php $date = $this->formatDate($snapshot['taken_at']); @endphp
            <div class="flex flex-wrap items-center gap-3 p-3" wire:key="snapshot-{{ $snapshot['name'] }}">
                <div class="min-w-0 flex-1 basis-56">
                    <div class="flex flex-wrap items-center gap-2 font-semibold">
                        <span class="first-letter:uppercase">{{ $date }}</span>
                        @if ($loop->first)
                            <flux:badge size="sm" color="green">{{ __('admin.snapshots.latest') }}</flux:badge>
                        @endif
                    </div>
                    <div class="text-xs text-zinc-500">{{ max(1, (int) round($snapshot['size'] / 1024)) }} Ko</div>
                </div>

                <div class="ms-auto flex items-center gap-1">
                    <flux:button size="sm" variant="ghost" icon="arrow-down-tray" :href="route('admin.snapshots.download', $snapshot['name'])" :aria-label="__('admin.snapshots.download')" />
                    <flux:button size="sm" icon="arrow-uturn-left" wire:click="restore('{{ $snapshot['name'] }}')"
                        wire:confirm.prompt="{{ __('admin.snapshots.restore_confirm', ['date' => $date, 'word' => __('admin.snapshots.restore_word')]) }}|{{ __('admin.snapshots.restore_word') }}">
                        {{ __('admin.snapshots.restore') }}
                    </flux:button>
                </div>
            </div>
        @empty
            <div class="p-6 text-center text-sm text-zinc-500">{{ __('admin.snapshots.empty') }}</div>
        @endforelse
    </div>
</section>
