<div class="space-y-3">
    <flux:heading size="lg" level="2">{{ __('stats.cookies.heading') }}</flux:heading>
    <flux:subheading>{{ __('stats.cookies.help') }}</flux:subheading>

    @if ($summary->isEmpty())
        <flux:callout icon="information-circle">{{ __('stats.cookies.empty') }}</flux:callout>
    @else
        <div class="grid grid-cols-3 gap-2 sm:gap-4">
            <div class="rounded-2xl border border-brand-line bg-white p-3 text-center">
                <div class="text-2xl font-bold tabular-nums" data-kilos>{{ $summary->kilos() }}</div>
                <div class="text-xs text-zinc-600">{{ __('stats.cookies.kilos_planned') }}</div>
            </div>
            <div class="rounded-2xl border border-brand-line bg-white p-3 text-center">
                <div class="text-2xl font-bold tabular-nums" data-families>{{ $summary->families() }}</div>
                <div class="text-xs text-zinc-600">{{ trans_choice('stats.cookies.families_engaged', $summary->families()) }}</div>
            </div>
            <div class="rounded-2xl border border-brand-line bg-white p-3 text-center">
                <div class="text-2xl font-bold tabular-nums" data-unchosen>{{ $summary->recipesWithoutCommitment() }}</div>
                <div class="text-xs text-zinc-600">{{ trans_choice('stats.cookies.recipes_unchosen', $summary->recipesWithoutCommitment()) }}</div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-brand-line bg-white">
            <table class="w-full text-sm">
                <thead class="bg-brand-sand text-left">
                    <tr>
                        <th class="px-3 py-2">{{ __('stats.cookies.recipe') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('stats.cookies.families') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('stats.cookies.kilos') }}</th>
                    </tr>
                </thead>
                @foreach ($summary->groups() as $category => $rows)
                    <tbody wire:key="category-{{ $loop->index }}">
                        <tr class="border-t border-brand-line bg-brand-cream">
                            <th colspan="3" scope="colgroup" class="px-3 py-1.5 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">{{ $category }}</th>
                        </tr>
                        @foreach ($rows as $row)
                            <tr class="border-t border-brand-line" wire:key="recipe-{{ $row['recipe']->id }}">
                                <td class="px-3 py-2">
                                    <flux:link :href="route('cookies.show', $row['recipe'])" wire:navigate variant="ghost">{{ $row['recipe']->title }}</flux:link>
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row['families'] }}</td>
                                <td @class(['px-3 py-2 text-right tabular-nums', 'bg-red-100 text-red-900' => $row['kilos'] === 0])>{{ __('stats.cookies.kilos_value', ['kilos' => $row['kilos']]) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                @endforeach
                <tfoot class="bg-brand-sand font-bold">
                    <tr class="border-t border-brand-line">
                        <th scope="row" class="px-3 py-2 text-left">{{ __('stats.cookies.total') }}</th>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $summary->families() }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ __('stats.cookies.kilos_value', ['kilos' => $summary->kilos()]) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="text-xs text-zinc-600">{{ __('stats.cookies.families_note') }}</p>
    @endif
</div>
