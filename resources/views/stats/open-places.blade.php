@php
    use App\Planning\OpenPlacesSummary;

    $tone = fn (?array $cell): string => match (true) {
        $cell === null => 'text-zinc-400',
        $cell['open'] === 0 => 'bg-emerald-100 text-emerald-900',
        $cell['open'] * 2 <= $cell['capacity'] => 'bg-amber-100 text-amber-900',
        default => 'bg-red-100 text-red-900',
    };
    $label = fn (?array $cell): string => $cell === null
        ? __('stats.open_places.none')
        : __('stats.open_places.cell', ['open' => $cell['open'], 'capacity' => $cell['capacity']]);
@endphp

<div class="space-y-3">
    <flux:heading size="lg" level="2">{{ __('stats.open_places.heading') }}</flux:heading>
    <flux:subheading>{{ __('stats.open_places.help') }}</flux:subheading>

    @if ($summary->isEmpty())
        <flux:callout icon="information-circle">{{ __('stats.open_places.empty') }}</flux:callout>
    @else
        <div class="overflow-x-auto rounded-2xl border border-brand-line bg-white">
            <table class="w-full text-sm" data-open-places>
                <thead class="bg-brand-sand text-left">
                    <tr>
                        <th class="sticky left-0 z-10 bg-brand-sand px-3 py-2">{{ __('stats.open_places.stand') }}</th>
                        @foreach ($summary->days() as $day)
                            <th class="px-3 py-2 text-center whitespace-nowrap">
                                {{ OpenPlacesSummary::dayLabel($day) }}
                                @if ($summary->isPreparation($day))
                                    <flux:badge size="sm" color="amber" class="mt-1 block">{{ __('planning.preparation') }}</flux:badge>
                                @endif
                            </th>
                        @endforeach
                        <th class="px-3 py-2 text-center">{{ __('stats.open_places.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summary->rows() as $row)
                        <tr class="border-t border-brand-line" wire:key="stand-{{ $row['stand']->id }}">
                            <th scope="row" class="sticky left-0 z-10 bg-white px-3 py-2 text-left font-medium">
                                {{ $row['stand']->name }}
                            </th>
                            @foreach ($summary->days() as $day)
                                <td class="px-3 py-2 text-center tabular-nums whitespace-nowrap {{ $tone($row['cells'][$day]) }}">{{ $label($row['cells'][$day]) }}</td>
                            @endforeach
                            <td class="px-3 py-2 text-center font-semibold tabular-nums whitespace-nowrap">{{ $label($row) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-brand-sand font-bold">
                    <tr class="border-t border-brand-line">
                        <th scope="row" class="sticky left-0 z-10 bg-brand-sand px-3 py-2 text-left">{{ __('stats.open_places.total') }}</th>
                        @foreach ($summary->days() as $day)
                            <td class="px-3 py-2 text-center tabular-nums whitespace-nowrap">{{ $label($summary->dayTotal($day)) }}</td>
                        @endforeach
                        <td class="px-3 py-2 text-center tabular-nums whitespace-nowrap" data-grand-total>{{ $label($summary->total()) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-600">
            <span><i class="mr-1 inline-block size-3 rounded-sm bg-red-200 align-[-2px]"></i>{{ __('stats.open_places.legend.empty') }}</span>
            <span><i class="mr-1 inline-block size-3 rounded-sm bg-amber-200 align-[-2px]"></i>{{ __('stats.open_places.legend.partial') }}</span>
            <span><i class="mr-1 inline-block size-3 rounded-sm bg-emerald-200 align-[-2px]"></i>{{ __('stats.open_places.legend.full') }}</span>
        </div>
    @endif
</div>
