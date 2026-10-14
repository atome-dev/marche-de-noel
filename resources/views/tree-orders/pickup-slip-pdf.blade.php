<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('trees.pdf.pickup_slip') }} {{ $order->number }}</title>
    @include('tree-orders.partials.pdf-style')
</head>
<body>
    <h1>{{ __('trees.title') }} — {{ config('marche.name') }}</h1>
    @include('tree-orders.partials.pdf-slip')
</body>
</html>
