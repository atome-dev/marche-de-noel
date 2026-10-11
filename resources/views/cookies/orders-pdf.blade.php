@php $ingredients = $summary->ingredients(); $format = fn (int $grams) => $grams ? \App\Support\Weight::format($grams) : '—'; @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('cookies.orders.title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1b1814; }
        h1 { font-size: 16px; margin: 0 0 2px; color: #1d4851; }
        p { margin: 0 0 12px; color: #575045; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #b9b2a3; padding: 4px 6px; }
        th { background: #f8f1dc; text-align: left; }
        td.number, th.number { text-align: right; white-space: nowrap; }
        tr.total td, tr.total th { background: #f8f1dc; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ config('marche.name') }} — {{ __('cookies.orders.title') }}</h1>
    <p>{{ __('planning.print_page.printed_on', ['date' => now()->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</p>

    <table>
        <thead>
            <tr>
                <th>{{ __('cookies.orders.family') }}</th>
                @foreach ($ingredients as $name)
                    <th class="number">{{ $name }}</th>
                @endforeach
                <th class="number">{{ __('cookies.orders.subtotal') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary->rows() as $row)
                <tr>
                    <th>{{ $row['user']->last_name }} {{ $row['user']->first_name }}</th>
                    @foreach ($row['grams'] as $grams)
                        <td class="number">{{ $format($grams) }}</td>
                    @endforeach
                    <td class="number"><strong>{{ $format($row['total']) }}</strong></td>
                </tr>
            @endforeach
            <tr class="total">
                <th>{{ __('cookies.orders.total') }}</th>
                @foreach ($summary->totals() as $grams)
                    <td class="number">{{ $format($grams) }}</td>
                @endforeach
                <td class="number">{{ $format($summary->grandTotal()) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
