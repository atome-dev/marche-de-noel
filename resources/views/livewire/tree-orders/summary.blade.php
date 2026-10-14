@php use App\Support\Money; @endphp

<section class="mx-auto w-full max-w-5xl space-y-6">
    @include('tree-orders.partials.manage-tabs', ['current' => 'summary'])

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:subheading>{{ __('trees.summary.help') }}</flux:subheading>
        <div class="flex gap-2">
            <flux:button size="sm" icon="table-cells" :href="route('trees.summary.xlsx')">{{ __('trees.summary.export_xlsx') }}</flux:button>
            <flux:button size="sm" icon="document-arrow-down" :href="route('trees.summary.pdf')">{{ __('trees.summary.export_pdf') }}</flux:button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-brand-line bg-white">
        <table class="w-full text-sm">
            <thead class="bg-brand-sand text-left">
                <tr>
                    <th class="px-4 py-2">{{ __('trees.summary.size') }}</th>
                    <th class="px-4 py-2 text-right">{{ __('trees.summary.quantity') }}</th>
                    <th class="px-4 py-2 text-right">{{ __('trees.summary.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summary->rows() as $row)
                    <tr class="border-t border-brand-line" wire:key="summary-{{ $row['key'] }}">
                        <td class="px-4 py-2">{{ $row['label'] }} <span class="text-zinc-500">({{ $row['height'] }})</span></td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $row['quantity'] }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ Money::format($row['amount_cents']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-brand-sand font-bold">
                <tr class="border-t border-brand-line">
                    <td class="px-4 py-2">{{ __('trees.summary.total') }}</td>
                    <td class="px-4 py-2 text-right tabular-nums" data-total-quantity>{{ $summary->totalQuantity() }}</td>
                    <td class="px-4 py-2 text-right tabular-nums">{{ Money::format($summary->totalAmountCents()) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <p class="text-sm text-zinc-600">{{ trans_choice('trees.summary.pending', $summary->pendingCount(), ['count' => $summary->pendingCount(), 'amount' => Money::format($summary->pendingAmountCents())]) }}</p>
</section>
