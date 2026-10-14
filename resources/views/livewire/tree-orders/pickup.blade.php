<section class="mx-auto w-full max-w-3xl space-y-6" @if ($refreshSeconds > 0) wire:poll.{{ $refreshSeconds }}s @endif>
    @include('tree-orders.partials.manage-tabs', ['current' => 'pickup'])

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-2xl font-bold tabular-nums" data-pickup-counter>{{ __('trees.pickup_list.counter', ['done' => $done, 'total' => $total]) }}</p>
        <flux:button size="sm" icon="printer" :href="route('trees.pickup.print')" target="_blank">{{ __('trees.pickup_list.print') }}</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('trees.manage.search')" :aria-label="__('trees.manage.search')" clearable />

    <div class="space-y-3">
        @forelse ($this->orders as $order)
            <article wire:key="pickup-{{ $order->id }}"
                @class([
                    'flex items-center gap-3 rounded-2xl border p-4',
                    'border-brand-line bg-white' => ! $order->isPickedUp(),
                    'border-green-200 bg-green-50' => $order->isPickedUp(),
                ])>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ $order->customer_name }} <span class="font-normal text-zinc-500">· {{ $order->number }}</span></p>
                    <p class="text-sm text-zinc-700">
                        @foreach ($order->lines as $line)
                            {{ $line->quantity }} × {{ $line->label }}@if (! $loop->last), @endif
                        @endforeach
                    </p>
                </div>

                @if ($order->isPickedUp())
                    <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" wire:click="togglePickup({{ $order->id }})">{{ __('trees.pickup_list.undo') }}</flux:button>
                @else
                    <flux:button variant="primary" icon="check" wire:click="togglePickup({{ $order->id }})">{{ __('trees.pickup_list.mark') }}</flux:button>
                @endif
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-brand-line p-6 text-center text-sm text-zinc-600">{{ __('trees.pickup_list.empty') }}</p>
        @endforelse
    </div>
</section>
