<x-mail::message>
# {{ __('stats.reminder.mail.greeting', ['first_name' => $manager->first_name, 'last_name' => $manager->last_name]) }}

{{ __('stats.reminder.mail.intro', ['stand' => $stand->name]) }}

@foreach ($days as $day => $slots)
## {{ $day }}

<x-mail::table>
| {{ __('stats.reminder.mail.slot') }} | {{ __('stats.reminder.mail.places') }} | {{ __('stats.reminder.mail.volunteers') }} |
|:--|:-:|:--|
@foreach ($slots as $slot)
| {{ $slot->starts_at }}–{{ $slot->ends_at }} | {{ $slot->takenCount() }} / {{ $slot->capacity }} | {{ $slot->registrations->map(fn ($registration) => str_replace('|', '/', $registration->displayName(full: true)))->join(', ') ?: '—' }} |
@endforeach
</x-mail::table>

@endforeach

<x-mail::button :url="route('planning', ['stand' => $stand->id])">
{{ __('stats.reminder.mail.action') }}
</x-mail::button>

{{ __('registration.mail.salutation') }}<br>
{{ config('marche.name') }}
</x-mail::message>
