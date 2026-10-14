<x-layouts::app :title="__('dashboard.title')">
    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
        @php $flyerBanner = file_exists(public_path('images/flyer-banner.webp')) ? asset('images/flyer-banner.webp') : null; @endphp

        <section class="overflow-hidden rounded-2xl bg-brand-ink text-white shadow-sm">
            @if ($flyerBanner)
                <img src="{{ $flyerBanner }}" alt="" class="aspect-[16/9] w-full object-cover sm:aspect-[5/2]">
            @endif

            <div class="relative overflow-hidden p-6 sm:p-8">
            @unless ($flyerBanner)
                <img src="{{ asset('images/fleur.png') }}" alt="" class="pointer-events-none absolute -end-10 -top-10 size-48 opacity-15">
            @endunless

            <p class="text-sm font-semibold uppercase tracking-widest text-brand-yellow">
                {{ __('dashboard.greeting', ['name' => auth()->user()->first_name]) }}
            </p>
            <h1 class="mt-2 font-display text-3xl leading-tight sm:text-4xl">{{ config('marche.name') }}</h1>

            @if (filled(config('marche.dates')))
                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach (config('marche.dates') as $date)
                        <li class="rounded-full bg-white/15 px-3 py-1 text-sm font-medium first-letter:uppercase">
                            {{ \Illuminate\Support\Carbon::parse($date)->locale(app()->getLocale())->translatedFormat('l j F Y') }}
                        </li>
                    @endforeach
                </ul>
            @endif

            <p class="mt-4 max-w-xl text-white/85">{{ __('dashboard.intro') }}</p>

            <a href="{{ route('tutorial') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-sm font-semibold hover:bg-white/25">
                <flux:icon name="play-circle" variant="mini" />
                {{ __('dashboard.tutorial') }}
            </a>
            </div>
        </section>

        @can('admin')
            @php $stats = app(\App\Planning\PlanningStats::class); @endphp

            <section class="rounded-2xl border border-brand-line bg-white p-5" data-planning-stats>
                <h2 class="font-display text-lg">{{ __('dashboard.stats.title') }}</h2>

                <dl class="mt-3 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-brand-sand/60 p-4">
                        <dt class="text-sm text-zinc-600">{{ __('dashboard.stats.volunteers') }}</dt>
                        <dd class="whitespace-nowrap font-display text-2xl sm:text-3xl" data-stat="volunteers">{{ $stats->volunteers() }}</dd>
                        @if ($stats->guests() > 0)
                            <dd class="text-xs text-zinc-600">{{ trans_choice('dashboard.stats.guests', $stats->guests(), ['count' => $stats->guests()]) }}</dd>
                        @endif
                    </div>
                    <div class="rounded-xl bg-brand-sand/60 p-4">
                        <dt class="text-sm text-zinc-600">{{ __('dashboard.stats.hours') }}</dt>
                        <dd class="whitespace-nowrap font-display text-2xl sm:text-3xl" data-stat="hours">{{ $stats->hoursLabel() }}</dd>
                    </div>
                </dl>

                <p class="mt-3 text-xs text-zinc-600">{{ __('dashboard.stats.note') }}</p>
            </section>
        @endcan

        <section class="grid gap-4 sm:grid-cols-2">
            <a href="{{ route('planning') }}" wire:navigate class="flex items-start gap-4 rounded-2xl border border-brand-line bg-white p-5 transition hover:border-brand-green hover:shadow-sm">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-sand text-brand-green">
                    <flux:icon name="calendar-days" />
                </div>
                <div>
                    <h2 class="font-display text-lg">{{ __('dashboard.modules.planning.title') }}</h2>
                    <p class="text-sm text-zinc-600">{{ __('dashboard.modules.planning.text') }}</p>
                    <span class="mt-2 inline-block text-sm font-semibold text-brand-green">{{ __('dashboard.open') }} →</span>
                </div>
            </a>

            <a href="{{ route('cookies.index') }}" wire:navigate class="flex items-start gap-4 rounded-2xl border border-brand-line bg-white p-5 transition hover:border-brand-green hover:shadow-sm">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-sand text-brand-green">
                    <flux:icon name="cake" />
                </div>
                <div>
                    <h2 class="font-display text-lg">{{ __('dashboard.modules.cakes.title') }}</h2>
                    <p class="text-sm text-zinc-600">{{ __('dashboard.modules.cakes.text') }}</p>
                    <span class="mt-2 inline-block text-sm font-semibold text-brand-green">{{ __('dashboard.open') }} →</span>
                </div>
            </a>

            <a href="{{ route('raffle.index') }}" wire:navigate class="flex items-start gap-4 rounded-2xl border border-brand-line bg-white p-5 transition hover:border-brand-green hover:shadow-sm">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-sand text-brand-green">
                    <flux:icon name="gift" />
                </div>
                <div>
                    <h2 class="font-display text-lg">{{ __('dashboard.modules.raffle.title') }}</h2>
                    <p class="text-sm text-zinc-600">{{ __('dashboard.modules.raffle.text') }}</p>
                    <span class="mt-2 inline-block text-sm font-semibold text-brand-green">{{ __('dashboard.open') }} →</span>
                </div>
            </a>

            <a href="{{ route('trees.index') }}" wire:navigate class="flex items-start gap-4 rounded-2xl border border-brand-line bg-white p-5 transition hover:border-brand-green hover:shadow-sm">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-sand text-brand-green">
                    <flux:icon name="sparkles" />
                </div>
                <div>
                    <h2 class="font-display text-lg">{{ __('dashboard.modules.trees.title') }}</h2>
                    <p class="text-sm text-zinc-600">{{ __('dashboard.modules.trees.text') }}</p>
                    <span class="mt-2 inline-block text-sm font-semibold text-brand-green">{{ __('dashboard.open') }} →</span>
                </div>
            </a>

            <a href="{{ route('documents.index') }}" wire:navigate class="flex items-start gap-4 rounded-2xl border border-brand-line bg-white p-5 transition hover:border-brand-green hover:shadow-sm">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-sand text-brand-green">
                    <flux:icon name="document-text" />
                </div>
                <div>
                    <h2 class="font-display text-lg">{{ __('dashboard.modules.documents.title') }}</h2>
                    <p class="text-sm text-zinc-600">{{ __('dashboard.modules.documents.text') }}</p>
                    <span class="mt-2 inline-block text-sm font-semibold text-brand-green">{{ __('dashboard.open') }} →</span>
                </div>
            </a>
        </section>
    </div>
</x-layouts::app>
