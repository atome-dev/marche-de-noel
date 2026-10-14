<x-mail::message>
# {{ __('trees.mail.greeting', ['name' => $order->customer_name]) }}

{{ __('trees.mail.paid.intro', ['number' => $order->number]) }}

@include('mail.partials.tree-order-lines')

@if ($pickupDate)
{{ __('trees.mail.paid.pickup', ['date' => $pickupDate->locale(app()->getLocale())->translatedFormat('l j F Y'), 'hours' => config('trees.pickup.hours'), 'place' => config('trees.pickup.place')]) }}
@endif

{{ __('trees.mail.salutation') }}<br>
{{ config('marche.name') }}
</x-mail::message>
