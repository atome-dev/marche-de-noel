@php
    // Visuels de l'année, facultatifs : affiche entière et bandeau recadré sur le dessin.
    $flyer = file_exists(public_path('images/flyer.webp')) ? asset('images/flyer.webp') : null;
    $flyerBanner = file_exists(public_path('images/flyer-banner.webp')) ? asset('images/flyer-banner.webp') : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-brand-cream font-sans text-brand-ink antialiased">
        <div @class(['min-h-svh', 'lg:grid lg:grid-cols-2' => $flyer])>
            @if ($flyer)
                {{-- Affiche du marché (ordinateur) --}}
                <div class="sticky top-0 hidden h-svh bg-[#283a7a] lg:block">
                    <img src="{{ $flyer }}" alt="{{ config('marche.name') }}" class="size-full object-cover object-top">
                </div>
            @endif

            <div class="flex min-h-svh flex-col items-center justify-center gap-6 px-4 py-8 sm:p-10">
                <div class="flex w-full max-w-md flex-col gap-6">
                    @if ($flyerBanner)
                        {{-- Dessin de l'affiche (mobile) --}}
                        <img src="{{ $flyerBanner }}" alt="" class="aspect-[16/9] w-full rounded-2xl object-cover shadow-sm lg:hidden">
                    @endif

                    <a href="{{ route('home') }}" class="flex flex-col items-center gap-3" wire:navigate>
                        <img src="{{ asset('images/logo.svg') }}" alt="{{ config('marche.school_name') }}" @class(['w-auto', 'h-14 lg:h-20' => $flyerBanner, 'h-20' => ! $flyerBanner])>
                        <span class="font-display text-xl text-brand-ink">{{ config('marche.name') }}</span>
                    </a>

                    <div class="rounded-2xl border border-brand-line bg-white p-6 shadow-sm sm:p-8">
                        {{ $slot }}
                    </div>

                    <div class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm text-zinc-600">
                        <a href="{{ route('tutorial') }}" class="underline underline-offset-4">{{ __('tutorial.link') }}</a>
                        <a href="{{ route('privacy') }}" class="underline underline-offset-4">{{ __('privacy.link') }}</a>
                    </div>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
