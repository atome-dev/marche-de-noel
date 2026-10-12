<x-mail::message>
# {{ __('invitations.mail.greeting', ['first_name' => $user->first_name]) }}

{{ $intro }}

{{ $purpose }}

@if ($personalMessage)
<x-mail::panel>
{{ $personalMessage }}
</x-mail::panel>
@endif

<x-mail::button :url="$url">
{{ __('invitations.mail.action') }}
</x-mail::button>

{{ __('invitations.mail.explain') }} {{ trans_choice('invitations.mail.validity', $validityDays, ['days' => $validityDays]) }}

{{ __('invitations.mail.ignore') }}

{{ __('registration.mail.salutation') }}<br>
{{ config('marche.name') }}
</x-mail::message>
