@php
    $returnAt = app(\App\Support\InfoBanner::class)->returnAt()?->setTimezone(config('marche.timezone'))->locale(app()->getLocale());
    $flyerBanner = file_exists(public_path('images/flyer-banner.webp')) ? asset('images/flyer-banner.webp') : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('errors.maintenance.page_title')])
        <meta http-equiv="refresh" content="60">
    </head>
    <body class="min-h-screen bg-brand-cream font-sans text-brand-ink antialiased">
        <main class="mx-auto flex min-h-svh w-full max-w-xl flex-col items-center justify-center gap-6 px-4 py-8 text-center sm:py-12">
            <div class="flex items-center gap-3">
                <x-app-logo-icon class="size-10" />
                <span class="font-display text-lg">{{ config('marche.name') }}</span>
            </div>

            @if ($flyerBanner)
                <img src="{{ $flyerBanner }}" alt="" class="aspect-[16/9] w-full rounded-2xl object-cover shadow-sm">
            @endif

            <div class="flex w-full flex-col items-center gap-4 rounded-2xl border border-brand-line bg-white px-5 py-8 shadow-sm sm:px-8">
                <span class="flex size-16 items-center justify-center rounded-full bg-brand-sand text-brand-yellow">
                    <flux:icon name="wrench-screwdriver" class="size-8" />
                </span>

                <h1 class="font-display text-2xl sm:text-3xl">{{ __('errors.maintenance.title') }}</h1>

                <p class="text-base text-brand-ink/80">{{ __('errors.maintenance.text') }}</p>

                <p data-maintenance-return class="rounded-xl bg-brand-sand px-4 py-3 text-base font-bold">
                    @if ($returnAt)
                        {{ __('errors.maintenance.back_at', [
                            'date' => $returnAt->translatedFormat('l j F'),
                            'time' => $returnAt->format('G').'h'.($returnAt->minute ? $returnAt->format('i') : ''),
                        ]) }}
                    @else
                        {{ __('errors.maintenance.back_soon') }}
                    @endif
                </p>

                <flux:button variant="primary" icon="arrow-path" href="{{ url()->current() }}" class="w-full sm:w-auto">
                    {{ __('errors.maintenance.retry') }}
                </flux:button>

                <p class="text-sm text-brand-ink/60">{{ __('errors.maintenance.auto_refresh') }}</p>
            </div>
        </main>
    </body>
</html>
