@php
    use App\Enums\TreeOrderStatus;
    use App\Support\Money;

    $formatDate = fn ($date) => $date->locale(app()->getLocale())->translatedFormat('l j F Y');
    $dropPoints = implode(' ou ', config('trees.cheque.drop_points'));
@endphp

<section class="mx-auto w-full max-w-3xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1" class="font-display">{{ __('trees.heading') }}</flux:heading>
            @if (filled(config('trees.description')))
                <flux:subheading>{{ config('trees.description') }}</flux:subheading>
            @endif
            @if (filled(config('trees.producer')))
                <p class="mt-1 text-sm text-zinc-500">{{ __('trees.producer', ['producer' => config('trees.producer')]) }}</p>
            @endif
        </div>

        @can('manage-tree-orders')
            <flux:button size="sm" icon="clipboard-document-list" :href="route('trees.manage')" wire:navigate>{{ __('trees.manage.link') }}</flux:button>
        @endcan
    </div>

    @if (session('tree_status'))
        @php $status = session('tree_status'); $number = session('tree_number'); @endphp
        <flux:callout
            :variant="$status === 'paid' ? 'success' : 'warning'"
            :icon="$status === 'paid' ? 'check-circle' : 'exclamation-triangle'"
            :heading="match ($status) {
                'paid' => __('trees.toasts.paid', ['number' => $number]),
                'pending' => __('trees.toasts.payment_pending', ['number' => $number]),
                default => __('trees.toasts.payment_aborted'),
            }" />
    @endif

    <div class="grid gap-3 rounded-2xl border border-brand-line bg-brand-sand/60 p-4 text-sm sm:grid-cols-2">
        @if ($deadline)
            <p class="flex items-start gap-2">
                <flux:icon name="clock" variant="mini" class="mt-0.5 shrink-0 text-brand-green" />
                <span class="font-semibold">{{ __('trees.deadline', ['date' => $formatDate($deadline)]) }}</span>
            </p>
        @endif
        @if ($pickupDate)
            <p class="flex items-start gap-2">
                <flux:icon name="map-pin" variant="mini" class="mt-0.5 shrink-0 text-brand-green" />
                <span>
                    {{ __('trees.pickup', ['date' => $formatDate($pickupDate), 'hours' => config('trees.pickup.hours')]) }}<br>
                    <span class="text-zinc-600">{{ config('trees.pickup.place') }}</span>
                </span>
            </p>
        @endif
    </div>

    @if ($this->isClosed)
        <flux:callout icon="lock-closed" :heading="__('trees.closed')" />
    @elseif ($this->sizes === [])
        <flux:callout icon="information-circle" :heading="__('trees.empty_catalog')" />
    @else
        <form wire:submit="orderByCheque" class="space-y-5">
            <div class="space-y-3">
                <flux:heading size="lg" level="2">{{ __('trees.form.sizes') }}</flux:heading>

                @foreach ($this->sizes as $size)
                    <div class="flex items-center gap-2 rounded-2xl border border-brand-line bg-white py-3 ps-4 pe-2 sm:gap-3 sm:p-4" wire:key="size-{{ $size->key }}">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold leading-snug">{{ $size->name }}</p>
                            <p class="flex flex-wrap gap-x-2 text-sm text-zinc-600">
                                <span class="whitespace-nowrap">{{ $size->height }}</span>
                                <span class="whitespace-nowrap font-semibold text-brand-ink">{{ Money::format($size->priceCents) }}</span>
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center sm:gap-2">
                            <flux:button type="button" variant="ghost" icon="minus" wire:click="decrement('{{ $size->key }}')"
                                :disabled="($quantities[$size->key] ?? 0) <= 0" :aria-label="__('trees.form.decrease', ['size' => $size->name])" />
                            <span class="w-5 text-center text-lg font-bold tabular-nums sm:w-6" aria-live="polite">{{ $quantities[$size->key] ?? 0 }}</span>
                            <flux:button type="button" variant="ghost" icon="plus" wire:click="increment('{{ $size->key }}')"
                                :disabled="($quantities[$size->key] ?? 0) >= $maxQuantity" :aria-label="__('trees.form.increase', ['size' => $size->name])" />
                        </div>
                    </div>
                @endforeach

                <flux:error name="quantities" />

                <div class="flex items-center justify-between rounded-2xl bg-brand-ink px-4 py-3 text-white">
                    <span class="font-semibold">{{ __('trees.form.total') }}</span>
                    <span class="text-xl font-bold tabular-nums">{{ Money::format($this->totalCents) }}</span>
                </div>
            </div>

            <div class="space-y-3">
                <flux:heading size="lg" level="2">{{ __('trees.form.contact') }}</flux:heading>
                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:input :label="__('trees.form.name')" :value="auth()->user()->first_name.' '.auth()->user()->last_name" readonly />
                    <flux:input :label="__('fields.email')" :value="auth()->user()->email" readonly />
                </div>
                <flux:input wire:model="phone" type="tel" autocomplete="tel" :label="__('trees.form.phone')" :description="__('trees.form.phone_help')" required />
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                @if ($this->onlinePayment)
                    <flux:button type="button" variant="primary" icon="credit-card" class="w-full sm:flex-1" wire:click="orderAndPay">
                        {{ __('trees.form.pay_online') }}
                    </flux:button>
                @endif
                <flux:button type="submit" :variant="$this->onlinePayment ? 'outline' : 'primary'" icon="document-text" class="w-full sm:flex-1">
                    {{ __('trees.form.pay_by_cheque') }}
                </flux:button>
            </div>

            @if (filled(config('trees.cheque.payee')))
                <p class="text-sm text-zinc-600">{{ __('trees.form.cheque_help', ['payee' => config('trees.cheque.payee'), 'places' => $dropPoints]) }}</p>
            @endif
        </form>
    @endif

    <div class="space-y-3">
        <flux:heading size="lg" level="2">{{ __('trees.mine.heading') }}</flux:heading>

        @forelse ($this->orders as $order)
            @php $status = $order->status(); @endphp
            <article class="space-y-3 rounded-2xl border border-brand-line bg-white p-4" wire:key="order-{{ $order->id }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-lg font-bold">{{ __('trees.mine.number', ['number' => $order->number]) }}</p>
                    <flux:badge :color="$status->color()" size="sm">{{ $status->label() }}</flux:badge>
                </div>

                <ul class="space-y-1 text-sm">
                    @foreach ($order->lines as $line)
                        <li class="flex justify-between gap-3">
                            <span>{{ $line->quantity }} × {{ $line->label }} <span class="text-zinc-500">({{ $line->height }})</span></span>
                            <span class="tabular-nums">{{ Money::format($line->amountCents()) }}</span>
                        </li>
                    @endforeach
                    <li class="flex justify-between gap-3 border-t border-brand-line pt-1 font-semibold">
                        <span>{{ __('trees.form.total') }}</span>
                        <span class="tabular-nums">{{ Money::format($order->total_cents) }}</span>
                    </li>
                </ul>

                @if ($status === TreeOrderStatus::Pending)
                    <div class="flex flex-wrap gap-2">
                        @if ($this->onlinePayment)
                            <flux:button size="sm" variant="primary" icon="credit-card" wire:click="pay({{ $order->id }})">{{ __('trees.mine.pay') }}</flux:button>
                        @endif
                        <flux:button size="sm" icon="arrow-down-tray" :href="route('trees.documents.order-form', $order)">{{ __('trees.mine.order_form') }}</flux:button>
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="cancel({{ $order->id }})"
                            wire:confirm="{{ __('trees.mine.cancel_confirm', ['number' => $order->number]) }}">{{ __('trees.mine.cancel') }}</flux:button>
                    </div>
                @elseif (in_array($status, [TreeOrderStatus::Paid, TreeOrderStatus::PickedUp], true))
                    @if ($status === TreeOrderStatus::Paid)
                        <p class="text-sm text-zinc-600">{{ __('trees.mine.show_on_pickup') }}</p>
                    @endif
                    <flux:button size="sm" icon="arrow-down-tray" :href="route('trees.documents.pickup-slip', $order)">{{ __('trees.mine.pickup_slip') }}</flux:button>
                @endif
            </article>
        @empty
            <p class="text-sm text-zinc-600">{{ __('trees.mine.empty') }}</p>
        @endforelse
    </div>
</section>
