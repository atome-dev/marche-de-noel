@php $infoBannerMessage = app(\App\Support\InfoBanner::class)->message(); @endphp
@if ($infoBannerMessage)
    <div role="alert" data-info-banner
        class="flex items-center justify-center gap-3 bg-red-600 px-4 py-4 text-center text-base font-bold text-white sm:gap-4 sm:py-5 sm:text-lg print:hidden">
        <flux:icon name="megaphone" class="size-7 shrink-0 sm:size-8" />
        <p class="min-w-0"><span class="sr-only">{{ __('layout.info_banner') }} : </span>{{ $infoBannerMessage }}</p>
    </div>
@endif
