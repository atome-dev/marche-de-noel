<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        @if (session()->has(\App\Auth\Impersonation::SESSION_KEY))
            <form method="POST" action="{{ route('impersonation.stop') }}" data-impersonation-banner
                class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 print:hidden">
                @csrf
                <span class="flex min-w-0 flex-1 basis-56 items-center gap-2">
                    <flux:icon name="eye" variant="mini" class="shrink-0" />
                    {{ __('admin.impersonation.banner', ['name' => auth()->user()->name]) }}
                </span>
                <flux:button type="submit" size="sm" variant="primary" icon="arrow-uturn-left">{{ __('admin.impersonation.stop') }}</flux:button>
            </form>
        @endif

        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
