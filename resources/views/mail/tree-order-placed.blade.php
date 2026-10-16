<x-mail::message>
# {{ __('trees.mail.greeting', ['name' => $order->customer_name]) }}

{{ __('trees.mail.placed.intro', ['number' => $order->number]) }}

@include('mail.partials.tree-order-lines')

@if ($shopUrl)
{{ __('trees.mail.placed.shop', ['name' => $order->customer_name]) }}

<x-mail::button :url="$shopUrl">
{{ __('trees.mail.placed.shop_action') }}
</x-mail::button>
@elseif ($onlinePayment)
{{ __('trees.mail.placed.pay_online') }}

<x-mail::button :url="route('trees.index')">
{{ __('trees.mail.placed.action') }}
</x-mail::button>
@endif

{{ __('trees.mail.placed.cheque', ['payee' => config('trees.cheque.payee'), 'places' => implode(' ou ', config('trees.cheque.drop_points'))]) }}

@if ($deadline)
{{ __('trees.mail.placed.deadline', ['date' => $deadline->locale(app()->getLocale())->translatedFormat('l j F Y')]) }}
@endif

{{ __('trees.mail.salutation') }}<br>
{{ config('marche.name') }}
</x-mail::message>
