<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('settings.profile.heading')" :subheading="__('settings.profile.description')">
        <x-auth-session-status class="mb-4" :status="session('status')" />
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="first_name" :label="__('fields.first_name')" required autocomplete="given-name" />
                <flux:input wire:model="last_name" :label="__('fields.last_name')" required autocomplete="family-name" />
            </div>

            <flux:input wire:model="email" :label="__('fields.email')" :description="__('settings.email_change.help')" type="email" required autocomplete="email" inputmode="email" />

            <flux:input
                wire:model="phone"
                :label="__('fields.phone')"
                :description="__('fields.phone_help')"
                type="tel"
                autocomplete="tel"
                inputmode="tel"
            />

            @include('livewire.partials.profile-preferences')

            <flux:button variant="primary" type="submit" class="w-full sm:w-auto" data-test="update-profile-button">
                {{ __('actions.save') }}
            </flux:button>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
