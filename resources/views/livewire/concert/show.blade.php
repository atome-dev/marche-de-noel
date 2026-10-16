@php
    use App\Support\Money;

    $timezone = config('marche.timezone');
@endphp

<section class="mx-auto w-full max-w-3xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1" class="font-display">{{ $this->event['title'] ?? __('concert.heading') }}</flux:heading>
            @if ($this->event && $this->event['startsAt'])
                @php $startsAt = $this->event['startsAt']->setTimezone($timezone)->locale(app()->getLocale()); @endphp
                <flux:subheading>{{ __('concert.when', ['date' => $startsAt->translatedFormat('l j F Y'), 'time' => $startsAt->format('G\hi')]) }}</flux:subheading>
            @endif
            @if (filled($this->event['place'] ?? null))
                <p class="mt-1 text-sm text-zinc-600">{{ $this->event['place'] }}</p>
            @endif
        </div>

        @can('manage-concert')
            @if (Route::has('concert.manage'))
                <flux:button size="sm" icon="ticket" :href="route('concert.manage')" wire:navigate>{{ __('concert.manage.link') }}</flux:button>
            @endif
        @endcan
    </div>

    @if (filled($this->event['tiers'] ?? []))
        <div class="flex flex-wrap gap-x-2 gap-y-1 rounded-2xl border border-brand-line bg-brand-sand/60 p-4 text-sm">
            <span class="font-semibold">{{ __('concert.prices') }} :</span>
            @foreach ($this->event['tiers'] as $tier)
                <span>{{ $tier['label'] }} <span class="font-semibold tabular-nums">{{ Money::format($tier['priceCents']) }}</span>@if (! $loop->last) ·@endif</span>
            @endforeach
        </div>
    @endif

    @if ($isConnected)
        <div class="space-y-3">
            <flux:heading size="lg" level="2">{{ __('concert.mine.heading') }}</flux:heading>

            @if ($this->ticketList === null)
                <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('concert.unavailable')" />
            @else
                @if ($this->ticketList->isStale)
                    <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('concert.stale')" />
                @endif

                @forelse ($this->myTickets as $ticket)
                    <article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-line bg-white p-4" wire:key="ticket-{{ $ticket->id }}">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $ticket->tier }} <span class="font-normal tabular-nums text-zinc-600">{{ Money::format($ticket->amountCents) }}</span></p>
                            <p class="text-sm text-zinc-600">{{ __('concert.mine.holder', ['name' => $ticket->holderName]) }}</p>
                            <p class="text-sm text-zinc-500">{{ __('concert.mine.ordered', ['date' => $ticket->orderedAt->setTimezone($timezone)->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($ticket->isScanned)
                                <flux:badge color="green" size="sm" icon="check">{{ __('concert.mine.scanned') }}</flux:badge>
                            @endif
                            @if ($ticket->ticketUrl)
                                <flux:button size="sm" variant="primary" icon="ticket" :href="$ticket->ticketUrl" target="_blank" rel="noopener">{{ __('concert.mine.open') }}</flux:button>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-zinc-600">{{ __('concert.mine.empty', ['email' => auth()->user()->email]) }}</p>
                @endforelse
            @endif
        </div>
    @endif

    @if ($widgetUrl)
        {{-- The widget sends its height: the iframe grows with it, no inner scroll bar. --}}
        <div class="-mx-4 sm:mx-0"
            x-data="{ height: 750 }"
            x-on:message.window="if (/(^|\.)helloasso(-sandbox)?\.com$/.test(new URL($event.origin).hostname) && Number($event.data?.height) > height) { height = Number($event.data.height) }">
            <iframe src="{{ $widgetUrl }}" title="{{ __('concert.widget_title') }}"
                allow="payment 'self' https://paymenthub.helloassopay.com https://helloasso.com" allowtransparency="true"
                class="block w-full border-0" x-bind:style="{ height: height + 'px' }" style="height: 750px"></iframe>
        </div>
    @elseif ($ticketingUrl)
        <flux:button variant="primary" icon:trailing="arrow-top-right-on-square" class="w-full" :href="$ticketingUrl" target="_blank" rel="noopener">{{ __('concert.book') }}</flux:button>
    @else
        <flux:callout icon="information-circle" :heading="__('concert.not_open')" />
    @endif
</section>
