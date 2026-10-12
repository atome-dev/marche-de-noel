<section class="mx-auto w-full max-w-3xl space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0 flex-1 basis-72">
            <flux:heading size="xl" level="1" class="font-display">{{ __('admin.snapshots.heading') }}</flux:heading>
            <flux:subheading>{{ __('admin.snapshots.description', ['days' => config('marche.snapshots.retention_days')]) }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="circle-stack" wire:click="create">{{ __('admin.snapshots.create') }}</flux:button>
    </div>

    <div class="flex items-start gap-2 rounded-xl border border-brand-line bg-white p-3 text-sm" data-last-check>
        @if ($this->lastCheck)
            <flux:icon name="clock" variant="mini" class="mt-0.5 shrink-0 text-brand-green" />
            <span>
                <span class="font-semibold">{{ __('admin.snapshots.last_check', ['date' => $this->formatDate($this->lastCheck['checked_at'])]) }}</span>
                {{ $this->lastCheck['changed'] ? __('admin.snapshots.last_check_changed') : __('admin.snapshots.last_check_unchanged') }}
            </span>
        @else
            <flux:icon name="exclamation-triangle" variant="mini" class="mt-0.5 shrink-0 text-amber-600" />
            <span>{{ __('admin.snapshots.never_checked') }}</span>
        @endif
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

    {{-- Journal du jour --}}
    <div class="space-y-3" data-snapshot-log>
        <div>
            <flux:heading size="lg" level="2" class="font-display">{{ __('admin.snapshots.log.heading') }}</flux:heading>
            <flux:subheading>{{ __('admin.snapshots.log.description', ['days' => config('logging.channels.snapshots.max_files', 7)]) }}</flux:subheading>
        </div>

        <div class="divide-y divide-brand-line overflow-hidden rounded-2xl border border-brand-line bg-white">
            @forelse ($this->log as $entry)
                @php
                    $context = $entry['context'];
                    $details = array_filter([
                        $context['file'] ?? null,
                        isset($context['size']) ? max(1, (int) round($context['size'] / 1024)).' Ko' : null,
                        isset($context['rows']) ? trans_choice('admin.snapshots.log.rows', (int) $context['rows'], ['count' => $context['rows']]) : null,
                        isset($context['deleted']) ? trans_choice('admin.snapshots.log.deleted', (int) $context['deleted'], ['count' => $context['deleted']]) : null,
                        isset($context['duration_ms']) ? $context['duration_ms'].' ms' : null,
                        ($context['origin'] ?? null) === 'site' ? __('admin.snapshots.log.from_site') : null,
                        $context['error'] ?? null,
                    ]);
                @endphp
                <div class="flex items-start gap-3 p-3 text-sm" wire:key="log-{{ $loop->index }}-{{ $entry['at']->timestamp }}">
                    <span class="w-12 shrink-0 font-semibold tabular-nums">{{ $entry['at']->setTimezone(config('marche.timezone'))->format('H:i') }}</span>
                    <div class="min-w-0 flex-1 space-y-1">
                        <div @class(['font-semibold', 'text-red-700' => $entry['level'] === 'error', 'text-amber-700' => $entry['level'] === 'warning'])>{{ $entry['message'] }}</div>
                        @if ($details)
                            <div class="break-words text-xs text-zinc-500">{{ implode(' · ', $details) }}</div>
                        @endif
                        @if ($entry['changes'])
                            <div class="flex flex-wrap gap-1">
                                @foreach ($entry['changes'] as $table => $difference)
                                    <flux:badge size="sm" :color="$difference > 0 ? 'green' : 'red'">{{ __('admin.snapshots.tables.'.$table) }} {{ $difference > 0 ? '+' : '−' }}{{ abs($difference) }}</flux:badge>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-sm text-zinc-500">{{ __('admin.snapshots.log.empty') }}</div>
            @endforelse
        </div>
    </div>
</section>
