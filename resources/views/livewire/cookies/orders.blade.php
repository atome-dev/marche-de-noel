@php $ingredients = $summary->ingredients(); $format = fn (int $grams) => $grams ? \App\Support\Weight::format($grams) : '—'; @endphp

<section class="mx-auto w-full max-w-5xl space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:link :href="route('cookies.index')" wire:navigate class="text-sm">← {{ __('cookies.index.heading') }}</flux:link>
            <flux:heading size="xl" level="1" class="mt-2 font-display">{{ __('cookies.orders.title') }}</flux:heading>
            <flux:subheading>
                @if ($locked)
                    {{ __('cookies.orders.locked') }}
                @elseif ($deadline)
                    {{ __('cookies.orders.open_until', ['date' => $deadline->locale(app()->getLocale())->translatedFormat('l j F Y')]) }}
                @endif
            </flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button size="sm" icon="arrow-down-tray" :href="route('cookies.orders.xlsx')">XLSX</flux:button>
            <flux:button size="sm" icon="arrow-down-tray" :href="route('cookies.orders.pdf')">PDF</flux:button>
        </div>
    </div>

    @if (blank($ingredients))
        <flux:callout variant="warning" icon="exclamation-triangle" :text="__('cookies.orders.no_ingredients')" />
    @elseif ($summary->rows()->isEmpty())
        <div class="rounded-xl border border-brand-line bg-white p-10 text-center text-zinc-500">{{ __('cookies.orders.empty') }}</div>
    @else
        <div class="overflow-x-auto rounded-xl border border-brand-line bg-white">
            <table class="w-full border-separate border-spacing-0 text-sm">
                <thead>
                    <tr class="bg-brand-sand text-left">
                        <th class="sticky left-0 border-b border-brand-line bg-brand-sand px-3 py-2">{{ __('cookies.orders.family') }}</th>
                        @foreach ($ingredients as $name)
                            <th class="border-b border-brand-line px-3 py-2 text-right">{{ $name }}</th>
                        @endforeach
                        <th class="border-b border-brand-line px-3 py-2 text-right">{{ __('cookies.orders.subtotal') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summary->rows() as $row)
                        <tr wire:key="family-{{ $row['user']->id }}">
                            <th class="sticky left-0 border-b border-zinc-200 bg-white px-3 py-2 text-left font-semibold">
                                {{ $row['user']->last_name }} {{ $row['user']->first_name }}
                            </th>
                            @foreach ($row['grams'] as $grams)
                                <td class="whitespace-nowrap border-b border-zinc-200 px-3 py-2 text-right">{{ $format($grams) }}</td>
                            @endforeach
                            <td class="whitespace-nowrap border-b border-zinc-200 px-3 py-2 text-right font-semibold">{{ $format($row['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-brand-sand font-bold">
                        <th class="sticky left-0 bg-brand-sand px-3 py-2 text-left">{{ __('cookies.orders.total') }}</th>
                        @foreach ($summary->totals() as $grams)
                            <td class="whitespace-nowrap px-3 py-2 text-right">{{ $format($grams) }}</td>
                        @endforeach
                        <td class="whitespace-nowrap px-3 py-2 text-right">{{ $format($summary->grandTotal()) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</section>
