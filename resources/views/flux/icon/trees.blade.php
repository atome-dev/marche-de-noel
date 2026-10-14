{{-- Free icon (docs/visuels/trees.png), drawn in the current text color through a CSS mask. --}}

@props([
    'variant' => 'outline',
])

@php
    $classes = Flux::classes('inline-block shrink-0 bg-current')->add(
        match ($variant) {
            'outline', 'solid' => '[:where(&)]:size-6',
            'mini' => '[:where(&)]:size-5',
            'micro' => '[:where(&)]:size-4',
        },
    );

    // Inline image: a mask from another origin (CDN, preview) would be blocked.
    static $dataUri = null;
    $dataUri ??= 'data:image/png;base64,'.base64_encode((string) file_get_contents(public_path('images/icons/trees.png')));
    $mask = 'url('.$dataUri.') center / contain no-repeat';
@endphp

<span
    {{ $attributes->class($classes) }}
    style="-webkit-mask: {{ $mask }}; mask: {{ $mask }};"
    data-flux-icon
    aria-hidden="true"
    data-slot="icon"
></span>
