@php
    $formatDay = fn ($date, string $format) => ucfirst(\Illuminate\Support\Carbon::parse($date)->locale(app()->getLocale())->translatedFormat($format));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('planning.print_page.title')])
        <style>
            @page { size: A4 landscape; margin: 10mm; }
            @media print {
                .no-print { display: none !important; }
                body { background: #fff !important; }
                .print-cell { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
                tr { break-inside: avoid; }
                .print-day + .print-day { break-before: page; }
                thead { display: table-header-group; }
            }
        </style>
    </head>
    <body class="bg-white p-4 font-sans text-sm text-zinc-900 antialiased">
        <div class="no-print mb-4 flex gap-2">
            <flux:button variant="primary" icon="printer" onclick="window.print()">{{ __('planning.print_page.action') }}</flux:button>
            <flux:button :href="route('planning')">{{ __('planning.print_page.back') }}</flux:button>
        </div>

        <header class="mb-3 flex items-end justify-between gap-4 border-b border-zinc-300 pb-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-500">{{ config('marche.name') }}</p>
                <h1 class="font-display text-2xl font-bold">
                    {{ $stand ? $stand->name : __('planning.print_page.title') }}
                    @if ($day) — {{ $formatDay($day, 'l j F') }} @endif
                </h1>
                @if ($stand && $stand->managers->isNotEmpty())
                    <p class="text-xs text-zinc-600">
                        {{ __('planning.print_page.managers', ['names' => $stand->managers->map(fn ($manager) => $showContacts && $manager->phone ? $manager->name.' ('.$manager->phone.')' : $manager->name)->implode(', ')]) }}
                    </p>
                @endif
            </div>
            <p class="text-xs text-zinc-500">{{ __('planning.print_page.printed_on', ['date' => now()->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</p>
        </header>

        @if ($stand)
            {{-- Fiche détaillée d'un stand --}}
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-zinc-100 text-left">
                        <th class="w-56 border border-zinc-300 px-2 py-1.5">{{ __('planning.print_page.slot') }}</th>
                        <th class="border border-zinc-300 px-2 py-1.5">{{ __('planning.print_page.volunteers') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grid->days() as $date => $planningDay)
                        @foreach ($planningDay->rows as $row)
                            @foreach ($row->slots as $slot)
                                <tr>
                                    <td class="border border-zinc-300 px-2 py-1.5 align-top">
                                        <strong class="block">{{ $formatDay($slot->date, 'l j F') }}</strong>
                                        {{ $slot->starts_at }}–{{ $slot->ends_at }}
                                        @if ($slot->label) <span class="block text-xs text-zinc-600">{{ $slot->label }}</span> @endif
                                        <span class="block text-xs text-zinc-600">{{ $slot->takenCount() }} / {{ $slot->capacity }}</span>
                                    </td>
                                    <td class="border border-zinc-300 px-2 py-1.5 align-top">
                                        @foreach ($slot->registrations as $registration)
                                            <div>
                                                {{ $registration->displayName($showContacts) }}
                                                @if ($showContacts && $registration->user?->phone) — {{ $registration->user->phone }} @endif
                                                @if ($showContacts && $registration->message) <span class="text-xs text-zinc-600">« {{ $registration->message }} »</span> @endif
                                            </div>
                                        @endforeach
                                        @if (($missing = $slot->capacity - $slot->takenCount()) > 0)
                                            <div class="text-xs italic text-zinc-500">{{ trans_choice('planning.print_page.missing', $missing, ['count' => $missing]) }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        @else
            {{-- Un tableau par jour, colonnes de 30 minutes sur toute la plage horaire --}}
            @php $times = $grid->times(); @endphp
            @foreach ($grid->days() as $date => $planningDay)
                <section class="print-day mb-6">
                    <h2 class="mb-1 font-display text-base font-bold">{{ $formatDay($date, 'l j F') }}</h2>
                    <table class="w-full table-fixed border-collapse text-[9px] leading-tight">
                        <colgroup>
                            <col style="width: 13%">
                            @foreach ($times as $time)
                                <col>
                            @endforeach
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="border border-zinc-400 bg-zinc-100 px-1 py-1 text-left">{{ __('planning.stand_column') }}</th>
                                @foreach ($times as $time)
                                    <th class="border border-zinc-400 bg-zinc-100 px-0.5 py-1 text-left {{ str_ends_with($time, ':00') ? '' : 'font-normal text-zinc-500' }}">
                                        {{ str_ends_with($time, ':00') ? (int) substr($time, 0, 2).'h' : '30' }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($planningDay->rows as $row)
                                <tr>
                                    <th class="border border-zinc-400 px-1 py-1 text-left align-top text-[10px]">{{ $row->stand->name }}</th>
                                    @foreach ($row->cells as $cell)
                                        @if ($cell->slot === null)
                                            <td class="border border-zinc-300"></td>
                                        @else
                                            <td colspan="{{ $cell->span }}" class="print-cell overflow-hidden border-2 border-zinc-500 bg-zinc-100 px-1 py-0.5 align-top">
                                                <strong>{{ $cell->slot->takenCount() }}/{{ $cell->slot->capacity }}</strong>
                                                @foreach ($cell->slot->registrations as $registration)
                                                    <span class="block truncate">{{ $registration->displayName() }}</span>
                                                @endforeach
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endforeach
        @endif

        @fluxScripts
    </body>
</html>
