@if (config('marche.test_phase'))
    <div role="alert" data-test-phase-banner
        class="flex items-center justify-center gap-2 bg-orange-500 px-4 py-3 text-center text-sm font-bold text-white sm:text-base print:hidden">
        <flux:icon name="exclamation-triangle" class="size-5 shrink-0 sm:size-6" />
        <span>{{ __('layout.test_phase') }}</span>
    </div>
@endif
