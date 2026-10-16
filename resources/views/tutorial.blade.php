@php
    $title = __('tutorial.title');
    $content = __('tutorial.guides.'.$guide);
    $steps = collect($content['steps'])->only(array_keys($screens));
    $total = $steps->count();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')

        <style>
            @keyframes tutorial-tap {
                0% { transform: translate(-50%, -50%) scale(.55); opacity: .95; }
                70% { transform: translate(-50%, -50%) scale(1.5); opacity: 0; }
                100% { transform: translate(-50%, -50%) scale(1.5); opacity: 0; }
            }
            @keyframes tutorial-progress { from { transform: scaleX(0); } to { transform: scaleX(1); } }

            .tutorial-screen { opacity: 0; transition: opacity .5s ease; }
            .tutorial-screen[data-active] { opacity: 1; }
            .tutorial-tap::before, .tutorial-tap::after {
                content: ''; position: absolute; left: 50%; top: 50%; width: 3.25rem; height: 3.25rem;
                border-radius: 9999px; border: 3px solid #ed6e7d; animation: tutorial-tap 1.6s ease-out infinite;
            }
            .tutorial-tap::after { animation-delay: .8s; }
            .tutorial-progress { transform-origin: left; transform: scaleX(0); }
            [data-playing] .tutorial-progress { animation: tutorial-progress var(--tutorial-duration) linear forwards; }

            @media (prefers-reduced-motion: reduce) {
                .tutorial-screen { transition: none; }
                .tutorial-tap::before { animation: none; transform: translate(-50%, -50%); }
                .tutorial-tap::after { display: none; }
            }
        </style>
    </head>
    <body class="min-h-screen bg-brand-cream font-sans text-brand-ink antialiased">
        @include('partials.test-phase-banner')
        <main class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-8 sm:py-12">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <x-app-logo-icon class="size-10" />
                <span class="font-display text-lg">{{ config('marche.name') }}</span>
            </a>

            <header class="flex flex-col gap-2">
                <h1 class="font-display text-2xl sm:text-3xl">{{ $title }}</h1>
                <p class="text-zinc-600">{{ $content['intro'] }}</p>
            </header>

            @if (count($guides) > 1)
                <nav class="flex flex-wrap gap-2" aria-label="{{ __('tutorial.guides_label') }}">
                    @foreach ($guides as $key)
                        <a href="{{ route('tutorial', $key === 'benevoles' ? [] : ['guide' => $key]) }}" @if ($key === $guide) aria-current="page" @endif
                            @class(['rounded-full border px-4 py-2 text-sm font-semibold', 'border-brand-ink bg-brand-ink text-white' => $key === $guide, 'border-brand-line bg-white text-zinc-700 hover:border-brand-green' => $key !== $guide])>
                            {{ __('tutorial.guides.'.$key.'.name') }}
                        </a>
                    @endforeach
                </nav>
            @endif

            <div data-tutorial style="--tutorial-duration: 7s" class="grid items-start gap-6 lg:grid-cols-[minmax(0,20rem)_minmax(0,1fr)] lg:gap-10">
                {{-- Téléphone --}}
                <div class="mx-auto w-full max-w-[17rem] lg:sticky lg:top-8 lg:max-w-none">
                    <div class="relative aspect-[390/780] overflow-hidden rounded-[2rem] border-[10px] border-brand-ink bg-brand-cream shadow-lg">
                        @foreach ($steps as $key => $step)
                            <div class="tutorial-screen absolute inset-0" data-screen @if ($loop->first) data-active @endif>
                                <img src="{{ asset('images/tutoriel/'.$guide.'/'.$key.'.webp') }}" alt="{{ __('tutorial.screen', ['title' => $step['title']]) }}"
                                    width="640" height="1280" class="size-full object-cover object-top" @unless ($loop->first) loading="lazy" @endunless>
                                @if ($screens[$key])
                                    <span class="tutorial-tap pointer-events-none absolute size-0" style="left: {{ $screens[$key][0] }}%; top: {{ $screens[$key][1] }}%" aria-hidden="true"></span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex min-w-0 flex-col gap-4">
                    {{-- Commandes --}}
                    <div class="flex items-center gap-2">
                        <button type="button" data-previous class="flex size-10 items-center justify-center rounded-full border border-brand-line bg-white" aria-label="{{ __('tutorial.previous') }}">
                            <flux:icon name="chevron-left" variant="mini" />
                        </button>
                        <button type="button" data-toggle class="flex size-10 items-center justify-center rounded-full bg-brand-ink text-white"
                            data-label-pause="{{ __('tutorial.pause') }}" data-label-play="{{ __('tutorial.play') }}" aria-label="{{ __('tutorial.pause') }}">
                            <span data-icon-pause><flux:icon name="pause" variant="mini" /></span>
                            <span data-icon-play hidden><flux:icon name="play" variant="mini" /></span>
                        </button>
                        <button type="button" data-next class="flex size-10 items-center justify-center rounded-full border border-brand-line bg-white" aria-label="{{ __('tutorial.next') }}">
                            <flux:icon name="chevron-right" variant="mini" />
                        </button>
                        <span class="ms-2 text-sm font-semibold text-zinc-600" data-counter data-template="{{ __('tutorial.step', ['current' => '%c', 'total' => $total]) }}" aria-live="polite">
                            {{ __('tutorial.step', ['current' => 1, 'total' => $total]) }}
                        </span>
                    </div>

                    {{-- Étapes --}}
                    <ol class="flex flex-col gap-2">
                        @foreach ($steps as $key => $step)
                            <li>
                                <button type="button" data-step @if ($loop->first) data-active aria-current="step" @endif
                                    class="group relative flex w-full items-start gap-3 overflow-hidden rounded-2xl border border-brand-line bg-white p-4 text-left transition data-active:border-brand-green data-active:shadow-sm max-lg:not-data-active:hidden">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-sand text-sm font-bold group-data-active:bg-brand-ink group-data-active:text-white">{{ $loop->iteration }}</span>
                                    <span class="min-w-0">
                                        <span class="block font-display text-lg leading-tight">{{ $step['title'] }}</span>
                                        <span class="mt-1 block text-sm leading-relaxed text-zinc-700 lg:hidden lg:group-data-active:block">{{ $step['text'] }}</span>
                                    </span>
                                    <span class="tutorial-progress absolute inset-x-0 bottom-0 hidden h-1 bg-brand-green group-data-active:block" aria-hidden="true"></span>
                                </button>
                            </li>
                        @endforeach
                    </ol>

                    {{-- Points (mobile) --}}
                    <div class="flex justify-center gap-2 lg:hidden" aria-hidden="true">
                        @foreach ($steps as $key => $step)
                            <span data-dot @if ($loop->first) data-active @endif class="size-2 rounded-full bg-brand-line data-active:bg-brand-ink"></span>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap gap-2 pt-2">
                        @auth
                            @if ($guide === 'administrateurs')
                                <flux:button variant="primary" :href="route('admin.users')">{{ __('tutorial.cta_users') }}</flux:button>
                            @elseif ($guide === 'responsables')
                                <flux:button variant="primary" :href="route('stands.index')">{{ __('tutorial.cta_stands') }}</flux:button>
                            @else
                                <flux:button variant="primary" :href="route('planning')">{{ __('tutorial.cta_planning') }}</flux:button>
                            @endif
                        @else
                            <flux:button variant="primary" :href="route('register')">{{ __('tutorial.cta_register') }}</flux:button>
                            <flux:button :href="route('login')">{{ __('tutorial.cta_login') }}</flux:button>
                        @endauth
                    </div>
                </div>
            </div>

            <a href="{{ route('home') }}" class="text-sm font-semibold text-brand-green">← {{ __('tutorial.back') }}</a>
        </main>

        <script>
            (() => {
                const root = document.querySelector('[data-tutorial]');
                const all = selector => [...root.querySelectorAll(selector)];
                const screens = all('[data-screen]'), steps = all('[data-step]'), dots = all('[data-dot]');
                const counter = root.querySelector('[data-counter]'), toggle = root.querySelector('[data-toggle]');
                const duration = parseFloat(getComputedStyle(root).getPropertyValue('--tutorial-duration')) * 1000;
                let current = 0, timer = null;
                let playing = ! window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                const schedule = () => {
                    clearTimeout(timer);
                    root.toggleAttribute('data-playing', playing);
                    if (playing) timer = setTimeout(() => show(current + 1), duration);
                };

                const show = index => {
                    current = (index + steps.length) % steps.length;
                    [screens, steps, dots].forEach(list => list.forEach((element, i) => element.toggleAttribute('data-active', i === current)));
                    steps.forEach((step, i) => i === current ? step.setAttribute('aria-current', 'step') : step.removeAttribute('aria-current'));
                    counter.textContent = counter.dataset.template.replace('%c', current + 1);
                    // Restart the progress bar of the new step.
                    root.removeAttribute('data-playing');
                    void root.offsetWidth;
                    schedule();
                };

                const setPlaying = value => {
                    playing = value;
                    toggle.querySelector('[data-icon-pause]').hidden = ! playing;
                    toggle.querySelector('[data-icon-play]').hidden = playing;
                    toggle.setAttribute('aria-label', playing ? toggle.dataset.labelPause : toggle.dataset.labelPlay);
                    show(current);
                };

                root.querySelector('[data-previous]').addEventListener('click', () => show(current - 1));
                root.querySelector('[data-next]').addEventListener('click', () => show(current + 1));
                toggle.addEventListener('click', () => setPlaying(! playing));
                steps.forEach((step, i) => step.addEventListener('click', () => show(i)));
                document.addEventListener('visibilitychange', () => document.hidden ? clearTimeout(timer) : schedule());

                setPlaying(playing);
            })();
        </script>
    </body>
</html>
