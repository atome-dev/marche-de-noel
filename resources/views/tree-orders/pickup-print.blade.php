<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('trees.pickup_list.title')])
        <style>
            @page { size: A4 portrait; margin: 12mm; }
            @media print {
                .no-print { display: none !important; }
                body { background: #fff !important; }
                tr { break-inside: avoid; }
                thead { display: table-header-group; }
            }
        </style>
    </head>
    <body class="bg-white p-4 font-sans text-sm text-zinc-900 antialiased">
        <div class="no-print mb-4 flex gap-2">
            <flux:button variant="primary" icon="printer" onclick="window.print()">{{ __('planning.print_page.action') }}</flux:button>
            <flux:button :href="route('trees.pickup')">{{ __('planning.print_page.back') }}</flux:button>
        </div>

        <header class="mb-3 border-b border-zinc-300 pb-2">
            <p class="text-xs font-semibold uppercase tracking-widest text-zinc-500">{{ config('marche.name') }}</p>
            <h1 class="font-display text-2xl font-bold">{{ __('trees.pickup_list.title') }}</h1>
            <p class="text-zinc-600">{{ __('planning.print_page.printed_on', ['date' => now()->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</p>
        </header>

        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-zinc-100 text-left">
                    <th class="w-10 border border-zinc-300 px-2 py-1">{{ __('trees.pickup_list.picked') }}</th>
                    <th class="border border-zinc-300 px-2 py-1">{{ __('trees.manage.customer') }}</th>
                    <th class="border border-zinc-300 px-2 py-1">{{ __('trees.manage.details') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="border border-zinc-300 px-2 py-2 text-center text-lg">{{ $order->isPickedUp() ? '☑' : '☐' }}</td>
                        <td class="border border-zinc-300 px-2 py-2">
                            <span class="font-semibold">{{ $order->customer_name }}</span> · {{ $order->number }}<br>
                            <span class="text-zinc-600">{{ $order->customer_phone }}</span>
                        </td>
                        <td class="border border-zinc-300 px-2 py-2">
                            @foreach ($order->lines as $line)
                                {{ $line->quantity }} × {{ $line->label }} ({{ $line->height }})@if (! $loop->last)<br>@endif
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="border border-zinc-300 px-2 py-4 text-center">{{ __('trees.pickup_list.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>

        @fluxScripts
    </body>
</html>
