@php
    use App\Enums\TreeOrderStatus;
    use App\Enums\TreePaymentMethod;
    use App\Support\Money;
@endphp

<section class="mx-auto w-full max-w-5xl space-y-6">
    @include('tree-orders.partials.manage-tabs', ['current' => 'orders'])

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="sm:flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('trees.manage.search')" :aria-label="__('trees.manage.search')" clearable />
        </div>
        <div class="sm:w-56">
            <flux:select wire:model.live="status" :aria-label="__('trees.manage.filter')">
                <flux:select.option value="">{{ __('trees.manage.all') }}</flux:select.option>
                @foreach ($statuses as $option)
                    <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <flux:modal.trigger name="guest-order">
            <flux:button icon="plus">{{ __('trees.manage.guest.open') }}</flux:button>
        </flux:modal.trigger>
    </div>

    @if ($this->orders->isNotEmpty())
        <p class="text-sm text-zinc-600">{{ trans_choice('trees.manage.count', $this->orders->count(), ['count' => $this->orders->count()]) }}</p>
    @endif

    <div class="space-y-3">
        @forelse ($this->orders as $order)
            @php $orderStatus = $order->status(); @endphp
            <article wire:key="manage-order-{{ $order->id }}"
                @class([
                    'grid gap-3 rounded-2xl border border-brand-line bg-white p-4 md:grid-cols-[minmax(0,14rem)_minmax(0,1fr)_auto] md:items-center',
                    'opacity-60' => in_array($orderStatus, [TreeOrderStatus::Cancelled, TreeOrderStatus::Expired], true),
                ])>
                <div class="min-w-0">
                    <p class="flex flex-wrap items-center gap-2">
                        <span class="font-bold">{{ $order->number }}</span>
                        <flux:badge :color="$orderStatus->color()" size="sm">{{ $orderStatus->label() }}</flux:badge>
                    </p>
                    <p class="truncate font-semibold">{{ $order->customer_name }}</p>
                    <p class="text-sm text-zinc-600"><a href="tel:{{ $order->customer_phone }}" class="underline">{{ $order->customer_phone }}</a></p>
                    @if ($order->customer_email)
                        <p class="truncate text-sm text-zinc-600">{{ $order->customer_email }}</p>
                    @endif
                </div>

                <div class="text-sm">
                    <ul>
                        @foreach ($order->lines as $line)
                            <li>{{ $line->quantity }} × {{ $line->label }} <span class="text-zinc-500">({{ $line->height }})</span></li>
                        @endforeach
                    </ul>
                    <p class="mt-1 font-semibold">
                        {{ Money::format($order->total_cents) }}
                        @if ($order->payment_method)
                            <span class="font-normal text-zinc-600">· {{ $order->payment_method->label() }}</span>
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 md:justify-end">
                    @if (! $order->isCancelled() && ! $order->isPaid())
                        <flux:button size="sm" variant="primary" icon="banknotes" wire:click="markCheque({{ $order->id }})">{{ __('trees.manage.mark_cheque') }}</flux:button>
                    @elseif ($order->payment_method === TreePaymentMethod::Cheque && ! $order->isCancelled() && ! $order->isPickedUp())
                        <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" wire:click="unmarkCheque({{ $order->id }})">{{ __('trees.manage.unmark_cheque') }}</flux:button>
                    @endif

                    <flux:dropdown position="bottom" align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="__('actions.more')" />
                        <flux:menu>
                            <flux:menu.item icon="document-text" :href="route('trees.documents.order-form', $order)">{{ __('trees.mine.order_form') }}</flux:menu.item>
                            @if ($order->isPaid() && ! $order->isCancelled())
                                <flux:menu.item icon="ticket" :href="route('trees.documents.pickup-slip', $order)">{{ __('trees.mine.pickup_slip') }}</flux:menu.item>
                            @endif
                            @unless ($order->isCancelled())
                                <flux:menu.separator />
                                <flux:menu.item icon="x-mark" variant="danger" wire:click="cancelOrder({{ $order->id }})"
                                    wire:confirm="{{ __('trees.manage.cancel_confirm', ['number' => $order->number, 'name' => $order->customer_name]) }}">{{ __('trees.manage.cancel') }}</flux:menu.item>
                            @endunless
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-brand-line p-6 text-center text-sm text-zinc-600">{{ __('trees.manage.empty') }}</p>
        @endforelse
    </div>

    <flux:modal name="guest-order" class="w-full max-w-lg">
        <form wire:submit="createGuestOrder" class="space-y-4">
            <flux:heading size="lg">{{ __('trees.manage.guest.heading') }}</flux:heading>

            <flux:input wire:model="guestName" :label="__('trees.form.name')" required />
            <flux:input wire:model="guestPhone" type="tel" :label="__('trees.form.phone')" required />
            <flux:input wire:model="guestEmail" type="email" :label="__('fields.email')" />

            <div class="space-y-2">
                @foreach ($this->sizes as $size)
                    <div class="flex items-center justify-between gap-3" wire:key="guest-size-{{ $size->key }}">
                        <span class="text-sm">{{ $size->name }} <span class="text-zinc-500">({{ $size->height }}, {{ Money::format($size->priceCents) }})</span></span>
                        <div class="w-24 shrink-0">
                            <flux:input type="number" min="0" :max="$maxQuantity" wire:model="guestQuantities.{{ $size->key }}" :aria-label="$size->name" />
                        </div>
                    </div>
                @endforeach
                <flux:error name="guestQuantities" />
            </div>

            <flux:checkbox wire:model="guestChequeReceived" :label="__('trees.manage.guest.cheque_received')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('actions.cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('trees.manage.guest.submit') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
