@php
    use App\Support\Money;
@endphp

<x-layouts::app :title="__('trees.shop.heading')">
    <section class="mx-auto w-full max-w-3xl space-y-5">
        <div>
            <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('trees.index')" wire:navigate>{{ __('trees.shop.back') }}</flux:button>
            <flux:heading size="xl" level="1" class="mt-2 font-display">{{ __('trees.shop.heading') }}</flux:heading>
            <flux:subheading>{{ __('trees.shop.intro', ['number' => $order->number]) }}</flux:subheading>
        </div>

        <ul class="space-y-1 rounded-2xl border border-brand-line bg-brand-sand/60 p-4 text-sm">
            @foreach ($order->lines as $line)
                <li class="flex justify-between gap-3">
                    <span>{{ $line->quantity }} × {{ $line->label }} <span class="text-zinc-500">({{ $line->height }})</span></span>
                    <span class="tabular-nums">{{ Money::format($line->amountCents()) }}</span>
                </li>
            @endforeach
            <li class="flex justify-between gap-3 border-t border-brand-line pt-1 font-semibold">
                <span>{{ __('trees.form.total') }}</span>
                <span class="tabular-nums">{{ Money::format($order->total_cents) }}</span>
            </li>
        </ul>

        <flux:callout icon="information-circle" :heading="__('trees.shop.pay_as', ['name' => $order->customer_name])" :text="__('trees.shop.validation')" />

        {{-- The widget sends its height: the iframe grows with it, no inner scroll bar. --}}
        <div class="-mx-4 sm:mx-0"
            x-data="{ height: 750 }"
            x-on:message.window="if (/(^|\.)helloasso\.com$/.test(new URL($event.origin).hostname) && Number($event.data?.height) > height) { height = Number($event.data.height) }">
            <iframe src="{{ $widgetUrl }}" title="{{ __('trees.shop.widget_title') }}"
                allow="payment 'self' https://paymenthub.helloassopay.com https://helloasso.com" allowtransparency="true"
                class="block w-full border-0" x-bind:style="{ height: height + 'px' }" style="height: 750px"></iframe>
        </div>
    </section>
</x-layouts::app>
