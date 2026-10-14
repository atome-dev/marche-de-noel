@php $pickupDate = app(\App\TreeOrders\TreeCatalog::class)->pickupDate(); @endphp
<div class="slip">
    <h2>{{ __('trees.pdf.pickup_slip') }}</h2>
    <p class="big">{{ __('trees.pdf.number', ['number' => $order->number]) }} — {{ $order->customer_name }}</p>
    @if ($pickupDate)
        <p>{{ __('trees.pdf.pickup', ['date' => $pickupDate->locale(app()->getLocale())->translatedFormat('l j F Y'), 'hours' => config('trees.pickup.hours')]) }}</p>
    @endif
    <p>{{ config('trees.pickup.place') }}</p>
    <table>
        <thead>
            <tr>
                <th>{{ __('trees.pdf.article') }}</th>
                <th class="number">{{ __('trees.pdf.quantity') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->lines as $line)
                <tr>
                    <td>{{ $line->label }} — {{ $line->height }}</td>
                    <td class="number">{{ $line->quantity }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if ($order->isPaid())
        <p class="muted" style="margin-top: 6px;">{{ __('trees.pdf.paid', ['date' => $order->paid_at->setTimezone(config('marche.timezone'))->translatedFormat('j F Y'), 'method' => $order->payment_method?->label()]) }}</p>
    @endif
</div>
