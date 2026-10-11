@php
    $grid = $this->grid;
    $formatDay = fn ($date, string $format) => ucfirst(\Illuminate\Support\Carbon::parse($date)->locale(app()->getLocale())->translatedFormat($format));
@endphp

<section class="w-full space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1" class="font-display">{{ __('planning.heading') }}</flux:heading>
            <flux:subheading>{{ __('planning.description') }}</flux:subheading>
        </div>

        <flux:button
            icon="printer"
            size="sm"
            :href="route('planning.print', array_filter(['stand' => $standId, 'day' => $day]))"
            target="_blank"
            class="max-md:hidden"
        >
            {{ __('planning.print') }}
        </flux:button>
    </div>

    {{-- Filtres --}}
    <div class="grid grid-cols-2 gap-2 rounded-xl border border-brand-line bg-white p-3 md:flex md:flex-wrap md:items-end">
        <flux:select wire:model.live="standId" size="sm" class="col-span-2 md:w-64" :aria-label="__('planning.filters.stand')">
            <flux:select.option value="">{{ __('planning.filters.all_stands') }}</flux:select.option>
            @foreach ($this->allStands as $stand)
                <flux:select.option :value="$stand->id" wire:key="filter-stand-{{ $stand->id }}">{{ $stand->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="day" size="sm" class="max-md:hidden md:w-56" :aria-label="__('planning.filters.day')">
            <flux:select.option value="">{{ __('planning.filters.all_days') }}</flux:select.option>
            @foreach (config('marche.dates') as $marketDate)
                <flux:select.option :value="$marketDate">{{ $formatDay($marketDate, 'l j F') }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="availability" size="sm" class="col-span-2 md:w-56" :aria-label="__('planning.filters.availability')">
            <flux:select.option value="all">{{ __('planning.filters.all_slots') }}</flux:select.option>
            <flux:select.option value="free">{{ __('planning.filters.free') }}</flux:select.option>
            <flux:select.option value="mine">{{ __('planning.filters.mine') }}</flux:select.option>
        </flux:select>
    </div>

    {{-- Onglets des jours (mobile) --}}
    <div class="flex gap-1.5 md:hidden" role="tablist" aria-label="{{ __('planning.filters.day') }}">
        <button type="button" wire:click="$set('day', '')"
            @class(['flex-1 rounded-lg border px-2 py-2 text-xs font-bold', 'border-brand-ink bg-brand-ink text-white' => $day === '', 'border-brand-line bg-white text-zinc-600' => $day !== ''])>
            {{ __('planning.filters.all_days_short') }}
        </button>
        @foreach (config('marche.dates') as $marketDate)
            <button type="button" wire:click="$set('day', '{{ $marketDate }}')" wire:key="tab-{{ $marketDate }}"
                @class(['flex-1 rounded-lg border px-2 py-2 text-xs font-bold', 'border-brand-ink bg-brand-ink text-white' => $day === $marketDate, 'border-brand-line bg-white text-zinc-600' => $day !== $marketDate])>
                {{ $formatDay($marketDate, 'D j') }}
            </button>
        @endforeach
    </div>

    @if ($grid->isEmpty())
        <div class="rounded-xl border border-brand-line bg-white p-10 text-center text-zinc-500">
            {{ __('planning.empty') }}
        </div>
    @else
        @php $times = $grid->times(); @endphp

        {{-- Un tableau par jour, avec les stands ouverts ce jour-là --}}
        @foreach ($grid->days() as $date => $planningDay)
            <section class="space-y-2 pt-4 first:pt-0" data-day="{{ $date }}" wire:key="day-{{ $date }}">
                <h2 class="font-display text-xl">{{ $formatDay($date, 'l j F') }}</h2>

                {{-- Grille (ordinateur) : colonnes de 30 minutes sur toute la plage horaire --}}
                <div class="max-md:hidden overflow-x-auto rounded-xl border border-brand-line bg-white shadow-sm">
                    <table class="w-full table-fixed border-separate border-spacing-0 text-sm" style="min-width: {{ 190 + count($times) * 38 }}px">
                        <colgroup>
                            <col style="width: 190px">
                            @foreach ($times as $time)
                                <col>
                            @endforeach
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="sticky left-0 z-20 border-b border-r border-brand-line bg-brand-sand px-3 py-2 text-left font-display">
                                    {{ __('planning.stand_column') }}
                                </th>
                                @foreach ($times as $time)
                                    @php $fullHour = str_ends_with($time, ':00'); @endphp
                                    <th data-time="{{ $time }}" title="{{ $time }}" wire:key="time-{{ $date }}-{{ $time }}"
                                        @class([
                                            'border-b border-brand-line bg-brand-sand px-1 py-2 text-left text-xs',
                                            'border-l border-l-zinc-300 font-bold text-zinc-700' => $fullHour,
                                            'border-l border-l-zinc-200 font-normal text-zinc-400' => ! $fullHour,
                                        ])>
                                        {{ $fullHour ? (int) substr($time, 0, 2).'h' : '30' }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($planningDay->rows as $row)
                                @php $stand = $row->stand; $canManage = Gate::allows('update', $stand); @endphp
                                <tr data-stand="{{ $stand->id }}" wire:key="row-{{ $date }}-{{ $stand->id }}">
                                    <th class="sticky left-0 z-10 border-b border-r border-brand-line bg-white px-3 py-2 text-left align-top">
                                        <span class="block font-bold leading-tight">{{ $stand->name }}</span>
                                        @if ($stand->schoolClasses->isNotEmpty())
                                            <span class="mt-0.5 block text-xs font-normal text-zinc-500">
                                                {{ $stand->schoolClasses->pluck('name')->implode(', ') }}
                                                @if ($stand->is_open_to_all) · {{ __('stands.open_to_all') }} @endif
                                            </span>
                                        @endif
                                        @if ($canManage)
                                            <a href="{{ route('stands.edit', $stand) }}" wire:navigate class="mt-1 inline-block text-xs font-semibold text-brand-green hover:underline">{{ __('planning.manage_stand') }}</a>
                                        @endif
                                    </th>
                                    @foreach ($row->cells as $cell)
                                        @php $fullHour = str_ends_with($cell->time, ':00'); @endphp
                                        @if ($cell->slot === null)
                                            <td data-slot-empty aria-label="{{ __('planning.no_slot') }}" wire:key="cell-{{ $date }}-{{ $stand->id }}-{{ $cell->time }}"
                                                @class(['border-b border-brand-line bg-zinc-50', 'border-l border-l-zinc-300' => $fullHour, 'border-l border-l-zinc-200' => ! $fullHour])></td>
                                        @else
                                            <td data-slot-start="{{ $cell->time }}" colspan="{{ $cell->span }}" wire:key="cell-{{ $date }}-{{ $stand->id }}-{{ $cell->time }}"
                                                @class(['h-px border-b border-brand-line p-0.5 align-top', 'border-l border-l-zinc-300' => $fullHour, 'border-l border-l-zinc-200' => ! $fullHour])>
                                                @include('livewire.planning.partials.cell', ['slot' => $cell->slot, 'canManage' => $canManage, 'compact' => false])
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Liste (mobile) --}}
                <div class="space-y-3 md:hidden">
                    @foreach ($planningDay->rows as $row)
                        @php $stand = $row->stand; $canManage = Gate::allows('update', $stand); @endphp
                        <article class="overflow-hidden rounded-xl border border-brand-line bg-white" wire:key="card-{{ $date }}-{{ $stand->id }}">
                            <header class="px-4 pb-2 pt-3">
                                <h3 class="font-display text-lg leading-tight">{{ $stand->name }}</h3>
                                @if ($stand->schoolClasses->isNotEmpty())
                                    <p class="text-xs text-zinc-500">
                                        {{ $stand->schoolClasses->pluck('name')->implode(', ') }}
                                        @if ($stand->is_open_to_all) · {{ __('stands.open_to_all') }} @endif
                                    </p>
                                @endif
                            </header>
                            @foreach ($row->slots as $slot)
                                <div class="flex items-center justify-between gap-3 border-t border-brand-line px-4 py-2" wire:key="mobile-{{ $slot->id }}">
                                    <div class="text-sm">
                                        <strong class="block">{{ $slot->starts_at }}–{{ $slot->ends_at }}</strong>
                                        @if ($slot->label)
                                            <span class="block text-xs text-zinc-500">{{ $slot->label }}</span>
                                        @endif
                                    </div>
                                    <div class="w-40 shrink-0">
                                        @include('livewire.planning.partials.cell', ['slot' => $slot, 'canManage' => $canManage, 'compact' => true])
                                    </div>
                                </div>
                            @endforeach
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach

        {{-- Légende --}}
        <div class="flex flex-wrap gap-x-4 gap-y-1 px-1 pt-2 text-xs text-zinc-600">
            <span><i class="mr-1 inline-block size-3 rounded-sm bg-red-400 align-[-2px]"></i>{{ __('planning.legend.empty') }}</span>
            <span><i class="mr-1 inline-block size-3 rounded-sm bg-amber-400 align-[-2px]"></i>{{ __('planning.legend.partial') }}</span>
            <span><i class="mr-1 inline-block size-3 rounded-sm bg-emerald-500 align-[-2px]"></i>{{ __('planning.legend.full') }}</span>
            <span class="inline-flex items-center gap-1"><flux:icon name="lock-closed" variant="micro" />{{ __('planning.legend.reserved') }}</span>
        </div>
    @endif

    @include('livewire.planning.partials.slot-modal')
</section>
