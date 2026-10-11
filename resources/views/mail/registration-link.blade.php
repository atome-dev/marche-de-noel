<x-mail::message>
# {{ __('registration.mail.greeting') }}

{{ __('registration.mail.intro', ['name' => config('marche.name')]) }}

<x-mail::button :url="$url">
{{ __('registration.mail.action') }}
</x-mail::button>

{{ trans_choice('registration.mail.validity', $validityHours, ['hours' => $validityHours]) }}

{{ __('registration.mail.ignore') }}

{{ __('registration.mail.salutation') }}<br>
{{ config('marche.name') }}
</x-mail::message>
