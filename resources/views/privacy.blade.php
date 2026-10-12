@php
    $title = __('privacy.title');
    $replacements = ['email' => config('marche.privacy.contact_email'), 'market' => config('marche.name'), 'days' => config('marche.snapshots.retention_days')];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-brand-cream font-sans text-brand-ink antialiased">
        <main class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8 sm:py-12">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <x-app-logo-icon class="size-10" />
                <span class="font-display text-lg">{{ config('marche.name') }}</span>
            </a>

            <header class="flex flex-col gap-2">
                <h1 class="font-display text-2xl sm:text-3xl">{{ $title }}</h1>
                <p class="text-zinc-600">{{ __('privacy.intro', $replacements) }}</p>
            </header>

            @foreach (__('privacy.sections', $replacements) as $section)
                <section class="flex flex-col gap-3 rounded-2xl border border-brand-line bg-white p-5 sm:p-6">
                    <h2 class="font-display text-lg">{{ $section['title'] }}</h2>

                    @foreach ($section['paragraphs'] as $paragraph)
                        <p class="text-sm leading-relaxed">{{ $paragraph }}</p>
                    @endforeach

                    @isset($section['items'])
                        <dl class="flex flex-col gap-3">
                            @foreach ($section['items'] as $label => $text)
                                <div class="rounded-xl bg-brand-sand/60 p-3">
                                    <dt class="text-sm font-semibold">{{ $label }}</dt>
                                    <dd class="text-sm leading-relaxed text-zinc-700">{{ $text }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endisset

                    @isset($section['closing'])
                        <p class="text-sm leading-relaxed">{{ $section['closing'] }}</p>
                    @endisset
                </section>
            @endforeach

            <a href="{{ route('home') }}" class="text-sm font-semibold text-brand-green">← {{ __('privacy.back') }}</a>
        </main>
    </body>
</html>
