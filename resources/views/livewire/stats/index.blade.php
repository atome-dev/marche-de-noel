<section class="mx-auto w-full max-w-5xl space-y-6">
    <flux:heading size="xl" level="1">{{ __('stats.title') }}</flux:heading>

    @if (count($tabs) > 1)
        <flux:navbar class="-mx-2 overflow-x-auto">
            @foreach ($tabs as $name)
                <flux:navbar.item
                    :icon="['planning' => 'calendar-days', 'petits-gateaux' => 'cake'][$name]"
                    href="#"
                    wire:click.prevent="selectTab('{{ $name }}')"
                    :current="$tab === $name"
                    wire:key="tab-{{ $name }}"
                >{{ __('stats.tabs.'.$name) }}</flux:navbar.item>
            @endforeach
        </flux:navbar>
    @endif

    @if ($openPlaces)
        @include('stats.open-places', ['summary' => $openPlaces])
    @elseif ($cookies)
        @include('stats.cookies', ['summary' => $cookies])
    @endif
</section>
