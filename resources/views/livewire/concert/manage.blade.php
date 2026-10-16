@php
    use App\Support\Money;

    $timezone = config('marche.timezone');
@endphp

<section class="mx-auto w-full max-w-3xl space-y-6" wire:poll.30s>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('concert.index')" wire:navigate>{{ __('concert.title') }}</flux:button>
            <flux:heading size="xl" level="1" class="mt-2 font-display">{{ __('concert.manage.title') }}</flux:heading>
        </div>
        @if ($isConnected)
            <flux:button size="sm" icon="arrow-path" wire:click="refresh">{{ __('concert.manage.refresh') }}</flux:button>
        @endif
    </div>

    @if (! $isConnected)
        <flux:callout icon="information-circle" :heading="__('concert.manage.not_connected')" />
    @elseif ($this->ticketList === null)
        <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('concert.unavailable')" />
    @else
        @if ($this->ticketList->isStale)
            <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('concert.stale')" />
        @endif

        @php $summary = $this->ticketList->summary(); @endphp

        <div class="overflow-hidden rounded-2xl border border-brand-line bg-white">
            <table class="w-full text-sm">
                <thead class="bg-brand-sand/60">
                    <tr>
                        <th class="px-4 py-2 text-start">{{ __('concert.manage.tier') }}</th>
                        <th class="px-4 py-2 text-end">{{ __('concert.manage.count') }}</th>
                        <th class="px-4 py-2 text-end">{{ __('concert.manage.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summary['tiers'] as $tier)
                        <tr class="border-t border-brand-line">
                            <td class="px-4 py-2">{{ $tier['tier'] }}</td>
                            <td class="px-4 py-2 text-end tabular-nums">{{ $tier['count'] }}</td>
                            <td class="px-4 py-2 text-end tabular-nums">{{ Money::format($tier['amountCents']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-t border-brand-line font-semibold">
                        <td class="px-4 py-2">{{ __('concert.manage.total') }}</td>
                        <td class="px-4 py-2 text-end tabular-nums">{{ $summary['count'] }}</td>
                        <td class="px-4 py-2 text-end tabular-nums">{{ Money::format($summary['amountCents']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="flex items-center gap-2 rounded-2xl bg-brand-ink px-4 py-3 font-semibold text-white">
            <flux:icon name="qr-code" variant="mini" />
            {{ __('concert.manage.entries', ['scanned' => $summary['scanned'], 'count' => $summary['count']]) }}
        </p>

        <div class="space-y-3">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('concert.manage.search')" :aria-label="__('concert.manage.search')" clearable />
            <p class="text-sm text-zinc-600">{{ trans_choice('concert.manage.spectators', count($this->spectators), ['count' => count($this->spectators)]) }}</p>

            @forelse ($this->spectators as $ticket)
                <article class="flex flex-wrap items-start justify-between gap-2 rounded-2xl border border-brand-line bg-white p-4" wire:key="spectator-{{ $ticket->id }}">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $ticket->holderName }}</p>
                        <p class="text-sm">{{ $ticket->tier }} · <span class="tabular-nums">{{ Money::format($ticket->amountCents) }}</span></p>
                        <p class="text-sm text-zinc-600">{{ __('concert.manage.paid_by', ['name' => $ticket->payerName]) }} · <span class="break-all">{{ $ticket->payerEmail }}</span></p>
                        <p class="text-sm text-zinc-500">{{ __('concert.mine.ordered', ['date' => $ticket->orderedAt->setTimezone($timezone)->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</p>
                    </div>
                    @if ($ticket->isScanned)
                        <flux:badge color="green" size="sm" icon="check">{{ __('concert.mine.scanned') }}</flux:badge>
                    @endif
                </article>
            @empty
                <p class="text-sm text-zinc-600">{{ __('concert.manage.empty') }}</p>
            @endforelse
        </div>
    @endif
</section>
