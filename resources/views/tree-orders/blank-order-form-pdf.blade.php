@php
    use App\Support\Money;

    $catalog = app(\App\TreeOrders\TreeCatalog::class);
    $deadline = $catalog->deadline();
    $pickupDate = $catalog->pickupDate();
    $dropPoints = config('trees.cheque.drop_points');
    $locale = app()->getLocale();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('trees.pdf.order_form') }}</title>
    @include('tree-orders.partials.pdf-style')
    <style>
        td.blank { height: 18px; }
        td.field { width: 68%; }
    </style>
</head>
<body>
    <h1>{{ __('trees.title') }} — {{ config('marche.name') }}</h1>
    @if (filled(config('trees.description')))
        <p class="muted">{{ config('trees.description') }}</p>
    @endif
    @if (filled(config('trees.producer')))
        <p class="muted">{{ __('trees.producer', ['producer' => config('trees.producer')]) }}</p>
    @endif

    <h2>{{ __('trees.pdf.order_form') }}</h2>

    <table>
        <tr><th>{{ __('trees.pdf.last_name') }}</th><td class="blank field"></td></tr>
        <tr><th>{{ __('trees.pdf.first_name') }}</th><td class="blank field"></td></tr>
        <tr><th>{{ __('trees.pdf.phone') }}</th><td class="blank field"></td></tr>
        <tr><th>{{ __('trees.pdf.email_optional') }}</th><td class="blank field"></td></tr>
    </table>

    <table style="margin-top: 10px;">
        <thead>
            <tr>
                <th>{{ __('trees.pdf.article') }}</th>
                <th class="number">{{ __('trees.pdf.price') }}</th>
                <th class="number" style="width: 14%;">{{ __('trees.pdf.quantity') }}</th>
                <th class="number" style="width: 18%;">{{ __('trees.summary.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sizes as $size)
                <tr>
                    <td class="blank">{{ $size->name }} — {{ $size->height }}</td>
                    <td class="number">{{ Money::format($size->priceCents) }}</td>
                    <td></td>
                    <td></td>
                </tr>
            @endforeach
            <tr class="total">
                <th colspan="3">{{ __('trees.pdf.total') }}</th>
                <td class="blank"></td>
            </tr>
        </tbody>
    </table>

    @if ($deadline)
        <p class="strong" style="margin-top: 10px;">{{ __('trees.pdf.deadline', ['date' => $deadline->locale($locale)->translatedFormat('l j F Y')]) }}</p>
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
        <p class="strong">{{ __('trees.pdf.pickup', ['date' => $pickupDate->locale($locale)->translatedFormat('l j F Y'), 'hours' => config('trees.pickup.hours')]) }}</p>
        <p>{{ config('trees.pickup.place') }}</p>
    @endif

    <div class="slip">
        <p class="muted" style="text-align: center;">{{ __('trees.pdf.keep_slip') }}</p>
        <h2>{{ __('trees.pdf.pickup_slip') }}</h2>
        <table>
            <tr><th style="width: 32%;">{{ __('trees.pdf.name') }}</th><td class="blank"></td></tr>
        </table>
        @if ($pickupDate)
            <p style="margin-top: 6px;">{{ __('trees.pdf.pickup', ['date' => $pickupDate->locale($locale)->translatedFormat('l j F Y'), 'hours' => config('trees.pickup.hours')]) }}</p>
        @endif
        <p>{{ config('trees.pickup.place') }}</p>
        <table>
            <thead>
                <tr>
                    <th>{{ __('trees.pdf.article') }}</th>
                    <th class="number" style="width: 18%;">{{ __('trees.pdf.quantity') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sizes as $size)
                    <tr>
                        <td class="blank">{{ $size->name }} — {{ $size->height }}</td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
