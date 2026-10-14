@php use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('trees.summary.title') }}</title>
    @include('tree-orders.partials.pdf-style')
</head>
<body>
    <h1>{{ config('marche.name') }} — {{ __('trees.summary.title') }}</h1>
    <p class="muted">{{ __('planning.print_page.printed_on', ['date' => now()->locale(app()->getLocale())->translatedFormat('j F Y')]) }} · {{ __('trees.summary.help') }}</p>

    <table>
        <thead>
            <tr>
                <th>{{ __('trees.summary.size') }}</th>
                <th class="number">{{ __('trees.summary.quantity') }}</th>
                <th class="number">{{ __('trees.summary.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary->rows() as $row)
                <tr>
                    <td>{{ $row['label'] }} ({{ $row['height'] }})</td>
                    <td class="number">{{ $row['quantity'] }}</td>
                    <td class="number">{{ Money::format($row['amount_cents']) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <th>{{ __('trees.summary.total') }}</th>
                <td class="number">{{ $summary->totalQuantity() }}</td>
                <td class="number">{{ Money::format($summary->totalAmountCents()) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>{{ __('trees.summary.orders') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('trees.manage.customer') }}</th>
                <th>{{ __('trees.manage.details') }}</th>
                <th class="number">{{ __('trees.summary.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary->paidOrders() as $order)
                <tr>
                    <td>{{ $order->number }} — {{ $order->customer_name }}<br><span class="muted">{{ $order->customer_phone }}</span></td>
                    <td>
                        @foreach ($order->lines as $line)
                            {{ $line->quantity }} × {{ $line->label }}@if (! $loop->last)<br>@endif
                        @endforeach
                    </td>
                    <td class="number">{{ Money::format($order->total_cents) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
