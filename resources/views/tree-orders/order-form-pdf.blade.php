@php
    $catalog = app(\App\TreeOrders\TreeCatalog::class);
    $deadline = $catalog->deadline();
    $pickupDate = $catalog->pickupDate();
    $dropPoints = config('trees.cheque.drop_points');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('trees.pdf.order_form') }} {{ $order->number }}</title>
    @include('tree-orders.partials.pdf-style')
</head>
<body>
    <h1>{{ __('trees.title') }} — {{ config('marche.name') }}</h1>
    @if (filled(config('trees.description')))
        <p class="muted">{{ config('trees.description') }}</p>
    @endif
    @if (filled(config('trees.producer')))
        <p class="muted">{{ __('trees.producer', ['producer' => config('trees.producer')]) }}</p>
    @endif

    <h2>{{ __('trees.pdf.order_form') }} — {{ __('trees.pdf.number', ['number' => $order->number]) }}</h2>

    <table>
        <tr><th style="width: 32%;">{{ __('trees.pdf.name') }}</th><td>{{ $order->customer_name }}</td></tr>
        <tr><th>{{ __('trees.pdf.phone') }}</th><td>{{ $order->customer_phone }}</td></tr>
        <tr><th>{{ __('trees.pdf.email') }}</th><td>{{ $order->customer_email }}</td></tr>
    </table>

    <table style="margin-top: 10px;">
        <thead>
            <tr>
                <th>{{ __('trees.pdf.article') }}</th>
                <th class="number">{{ __('trees.pdf.price') }}</th>
                <th class="number">{{ __('trees.pdf.quantity') }}</th>
                <th class="number">{{ __('trees.summary.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->lines as $line)
                <tr>
                    <td>{{ $line->label }} — {{ $line->height }}</td>
                    <td class="number">{{ \App\Support\Money::format($line->unit_price_cents) }}</td>
                    <td class="number">{{ $line->quantity }}</td>
                    <td class="number">{{ \App\Support\Money::format($line->amountCents()) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <th colspan="3">{{ __('trees.pdf.total') }}</th>
                <td class="number">{{ \App\Support\Money::format($order->total_cents) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($deadline)
        <p class="strong" style="margin-top: 10px;">{{ __('trees.pdf.deadline', ['date' => $deadline->locale(app()->getLocale())->translatedFormat('l j F Y')]) }}</p>
    @endif
    @if (filled(config('trees.cheque.payee')))
        <p>{{ __('trees.pdf.cheque', ['payee' => config('trees.cheque.payee')]) }}</p>
        <ul>
            @foreach ($dropPoints as $place)
                <li>{{ $place }}</li>
            @endforeach
        </ul>
    @endif
    <p class="strong">{{ __('trees.pdf.rejected') }}</p>
    @if ($pickupDate)
        <p class="strong">{{ __('trees.pdf.pickup', ['date' => $pickupDate->locale(app()->getLocale())->translatedFormat('l j F Y'), 'hours' => config('trees.pickup.hours')]) }}</p>
        <p>{{ config('trees.pickup.place') }}</p>
    @endif
    <p>{{ __('trees.pdf.bring_slip') }}</p>

    @include('tree-orders.partials.pdf-slip')
</body>
</html>
