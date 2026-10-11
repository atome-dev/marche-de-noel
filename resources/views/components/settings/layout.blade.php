<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist aria-label="{{ __('settings.heading') }}">
            <flux:navlist.item :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" icon="user" wire:navigate>{{ __('settings.profile.heading') }}</flux:navlist.item>
            <flux:navlist.item :href="route('security.edit')" :current="request()->routeIs('security.edit')" icon="key" wire:navigate>{{ __('settings.password.heading') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
