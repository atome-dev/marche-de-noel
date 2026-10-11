<section class="mx-auto w-full max-w-5xl space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1" class="font-display">{{ __('cookies.index.heading') }}</flux:heading>
            <flux:subheading>{{ __('cookies.index.description') }}</flux:subheading>
        </div>

        @can('manage-cookies')
            <div class="flex flex-wrap gap-2">
                <flux:button size="sm" icon="table-cells" :href="route('cookies.orders')" wire:navigate>{{ __('cookies.orders.title') }}</flux:button>
                <flux:button size="sm" icon="tag" :href="route('cookies.categories')" wire:navigate>{{ __('cookies.categories.title') }}</flux:button>
                <flux:button size="sm" variant="primary" icon="plus" :href="route('cookies.create')" wire:navigate>{{ __('cookies.index.create') }}</flux:button>
            </div>
        @endcan
    </div>

    <x-auth-session-status :status="session('status')" />

    @if ($this->recipes->isEmpty())
        <div class="rounded-xl border border-brand-line bg-white p-10 text-center text-zinc-500">{{ __('cookies.index.empty') }}</div>
    @elseif ($this->mine->isEmpty())
        {{-- Aucune recette pour mes classes : toutes les recettes dans un seul bloc --}}
        <div class="space-y-3">
            <h2 class="font-display text-xl">{{ __('cookies.index.all') }}</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($this->recipes as $recipe)
                    @include('livewire.cookies.partials.card', ['recipe' => $recipe])
                @endforeach
            </div>
        </div>
    @else
        <div class="space-y-3">
            <div>
                <h2 class="font-display text-xl">{{ __('cookies.index.mine') }}</h2>
                <p class="text-sm text-zinc-600">{{ __('cookies.index.mine_help') }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($this->mine as $recipe)
                    @include('livewire.cookies.partials.card', ['recipe' => $recipe])
                @endforeach
            </div>
        </div>

        @if ($this->others->isNotEmpty())
            <div class="space-y-3">
                <h2 class="font-display text-xl">{{ __('cookies.index.others') }}</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($this->others as $recipe)
                        @include('livewire.cookies.partials.card', ['recipe' => $recipe])
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</section>
