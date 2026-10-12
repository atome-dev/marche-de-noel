{{-- Vignette d'une recette. Variable : $recipe --}}
<a href="{{ route('cookies.show', $recipe) }}" wire:navigate wire:key="recipe-{{ $recipe->id }}"
   class="group flex flex-col overflow-hidden rounded-2xl border border-brand-line bg-white transition hover:border-brand-green hover:shadow-sm">
    <div class="relative aspect-[4/3] bg-brand-sand">
        @if ($photo = $recipe->photos->first())
            <img src="{{ $photo->url() }}" alt="" loading="lazy" class="size-full object-cover">
        @else
            <div class="flex size-full items-center justify-center text-brand-yellow/60">
                <flux:icon name="cake" class="size-12" />
            </div>
        @endif

        @if ($recipe->is_committed)
            <span class="absolute left-2 top-2 rounded-full bg-brand-ink px-2 py-0.5 text-xs font-semibold text-white">{{ __('cookies.index.committed') }}</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-1 p-3">
        @if ($recipe->category)
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-green">{{ $recipe->category->name }}</span>
        @endif
        <h3 class="font-display leading-tight group-hover:underline">{{ $recipe->title }}</h3>
        @if ($recipe->schoolClasses->isNotEmpty())
            <p class="mt-auto pt-1 text-xs text-zinc-500">{{ $recipe->schoolClasses->pluck('name')->implode(', ') }}</p>
        @endif
    </div>
    @can('manage-cookies')
        @php($totalKg = (int) $recipe->total_kg)
        <p class="flex items-center gap-1.5 border-t border-brand-line px-3 py-2 text-xs {{ $totalKg > 0 ? 'font-semibold text-brand-green' : 'text-zinc-400' }}">
            <flux:icon name="scale" variant="micro" class="size-4" />
            {{ trans_choice('cookies.index.total_kg', $totalKg) }}
        </p>
    @endcan
</a>
