@php $recipe = $this->recipe; @endphp

<section class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:link :href="route('cookies.index')" wire:navigate class="text-sm">← {{ __('cookies.index.heading') }}</flux:link>

        <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
            <div>
                @if ($recipe->category)
                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-green">{{ $recipe->category->name }}</span>
                @endif
                <flux:heading size="xl" level="1" class="font-display">{{ $recipe->title }}</flux:heading>
                @if ($recipe->schoolClasses->isNotEmpty())
                    <p class="mt-1 text-sm text-zinc-600">{{ $recipe->schoolClasses->pluck('name')->implode(', ') }}</p>
                @endif
            </div>

            @can('manage-cookies')
                <flux:button size="sm" icon="pencil-square" :href="route('cookies.edit', $recipe)" wire:navigate>{{ __('cookies.show.edit') }}</flux:button>
            @endcan
        </div>
    </div>

    @if ($recipe->photos->isNotEmpty())
        <div class="flex snap-x gap-3 overflow-x-auto pb-1">
            @foreach ($recipe->photos as $photo)
                <img src="{{ $photo->url() }}" alt="{{ $recipe->title }}" loading="lazy" wire:key="photo-{{ $photo->id }}"
                     class="h-56 w-auto max-w-[85%] shrink-0 snap-start rounded-2xl object-cover sm:h-72">
            @endforeach
        </div>
    @endif

    {{-- Engagement et commandes --}}
    <div class="space-y-4 rounded-2xl border border-brand-line bg-white p-4 sm:p-6">
        <form wire:submit="saveCommitment" class="space-y-3">
            <flux:radio.group wire:model="quantity" variant="segmented" :label="__('cookies.show.quantity')">
                @foreach (config('marche.orders.quantities') as $choice)
                    <flux:radio :value="$choice" :label="$choice.' kg'" wire:key="quantity-{{ $choice }}" />
                @endforeach
            </flux:radio.group>
            <flux:error name="quantity" />

            <div class="flex flex-wrap items-center gap-2">
                <flux:button type="submit" variant="primary">{{ __('cookies.show.validate') }}</flux:button>

                @if ($this->commitment)
                    <flux:button variant="ghost" wire:click="withdraw" wire:confirm="{{ __('cookies.show.withdraw_confirm') }}">
                        {{ __('cookies.show.withdraw') }}
                    </flux:button>
                @endif
            </div>
        </form>

        @if ($this->commitment)
            <flux:callout variant="success" icon="check-circle" :text="__('cookies.show.committed', ['quantity' => $this->commitment->quantity_kg])" />
        @endif

        @if ($ordersCancelled)
            <flux:callout variant="warning" icon="exclamation-triangle" :text="__('cookies.show.orders_cancelled')" />
        @endif

        <flux:error name="orders" />

        @if ($this->orderables)
            <div class="space-y-2 border-t border-brand-line pt-4">
                <flux:heading size="sm">{{ __('cookies.show.orders_heading') }}</flux:heading>
                <p class="text-sm text-zinc-600">
                    @if ($this->locked)
                        {{ __('cookies.orders.locked') }}
                    @elseif ($this->deadline)
                        {{ __('cookies.show.deadline', ['date' => $this->deadline->locale(app()->getLocale())->translatedFormat('l j F Y')]) }}
                    @endif
                </p>

                @foreach ($this->orderables as $key => $orderable)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-brand-sand px-3 py-2" wire:key="orderable-{{ $key }}">
                        <div class="text-sm">
                            <strong>{{ $orderable['name'] }}</strong>
                            <span class="text-zinc-600">· {{ \App\Support\Weight::format($orderable['ordered'] ?? $orderable['grams']) }}</span>
                        </div>

                        @if ($orderable['ordered'] !== null)
                            <div class="flex items-center gap-2">
                                <flux:badge size="sm" color="green" icon="check">{{ __('cookies.show.ordered') }}</flux:badge>
                                @unless ($this->locked)
                                    <flux:button size="xs" variant="ghost" wire:click="cancelOrder('{{ $key }}')">{{ __('actions.cancel') }}</flux:button>
                                @endunless
                            </div>
                        @elseif (! $this->locked)
                            <flux:button size="sm" wire:click="order('{{ $key }}')">
                                {{ __('cookies.show.order', ['ingredient' => \Illuminate\Support\Str::lcfirst($orderable['name'])]) }}
                            </flux:button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Ingrédients --}}
    @if ($recipe->ingredients->isNotEmpty())
        <div class="rounded-2xl border border-brand-line bg-white p-4 sm:p-6">
            <h2 class="font-display text-lg">{{ __('cookies.show.ingredients') }}</h2>
            <ul class="mt-3 divide-y divide-zinc-200">
                @foreach ($recipe->ingredients as $ingredient)
                    <li class="flex justify-between gap-4 py-2 text-sm" wire:key="ingredient-{{ $ingredient->id }}">
                        <span>{{ $ingredient->name }}</span>
                        <span class="shrink-0 font-semibold">{{ $ingredient->quantityLabel() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Instructions --}}
    @if (filled($recipe->instructions))
        <div class="rounded-2xl border border-brand-line bg-white p-4 sm:p-6">
            <h2 class="font-display text-lg">{{ __('cookies.show.instructions') }}</h2>
            <div class="recipe-content mt-3 text-sm leading-relaxed">{!! \App\Support\HtmlSanitizer::clean($recipe->instructions) !!}</div>
        </div>
    @endif

    {{-- Vidéo et PDF --}}
    @if ($recipe->video_url)
        <div class="rounded-2xl border border-brand-line bg-white p-4 sm:p-6">
            <h2 class="font-display text-lg">{{ __('cookies.show.video') }}</h2>
            @if ($embed = $recipe->videoEmbedUrl())
                <div class="mt-3 aspect-video overflow-hidden rounded-xl">
                    <iframe src="{{ $embed }}" title="{{ $recipe->title }}" class="size-full" loading="lazy" allowfullscreen
                            allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>
            @else
                <flux:link :href="$recipe->video_url" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block">{{ __('cookies.show.watch_video') }}</flux:link>
            @endif
        </div>
    @endif

    @if ($pdf = $recipe->pdfLink())
        <flux:button icon="document-arrow-down" :href="$pdf" target="_blank" rel="noopener noreferrer">{{ __('cookies.show.pdf') }}</flux:button>
    @endif
</section>
