{{-- Informations importantes en tête de la page des recettes --}}
<div class="overflow-hidden rounded-2xl border border-brand-line bg-white">
    <h2 class="flex items-center gap-2 bg-brand-sand px-4 py-3 font-display text-lg">
        <flux:icon name="information-circle" variant="mini" class="text-brand-ink" />
        {{ __('cookies.index.info.heading') }}
    </h2>

    <div class="space-y-3 p-4 text-sm leading-relaxed text-zinc-700">
        <p class="font-semibold text-brand-ink">{{ __('cookies.index.info.intro') }}</p>

        <p class="flex items-start gap-2">
            <flux:icon name="cake" variant="mini" class="mt-0.5 shrink-0 text-brand-yellow" />
            <span><strong class="text-brand-ink">{{ __('cookies.index.info.quantity_label') }}</strong> {{ __('cookies.index.info.quantity') }}</span>
        </p>

        @if ($this->orderableIngredients !== '')
            <p class="flex items-start gap-2">
                <flux:icon name="shopping-bag" variant="mini" class="mt-0.5 shrink-0 text-brand-yellow" />
                <span>
                    <strong class="text-brand-ink">{{ __('cookies.index.info.orders_label') }}</strong>
                    {{ __('cookies.index.info.orders', ['ingredients' => $this->orderableIngredients]) }}
                    @if ($this->locked)
                        {{ __('cookies.index.info.orders_closed') }}
                    @elseif ($this->deadline)
                        <strong>{{ __('cookies.index.info.orders_until', ['date' => $this->deadline->locale(app()->getLocale())->translatedFormat('l j F Y')]) }}</strong>
                    @endif
                </span>
            </p>
        @endif

        <p class="flex items-start gap-2 rounded-lg border-l-4 border-brand-green bg-brand-green/10 px-3 py-2">
            <flux:icon name="sparkles" variant="mini" class="mt-0.5 shrink-0 text-brand-green" />
            <span><strong class="text-brand-ink">{{ __('cookies.index.info.organic_label') }}</strong> {{ __('cookies.index.info.organic') }}</span>
        </p>

        <p class="flex items-center justify-center gap-2 border-t border-brand-line pt-3 text-center font-semibold text-brand-ink">
            <flux:icon name="heart" variant="mini" class="shrink-0 text-brand-coral" />
            {{ __('cookies.index.info.thanks') }}
        </p>
    </div>
</div>
