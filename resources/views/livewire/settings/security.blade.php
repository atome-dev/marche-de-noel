<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('settings.password.heading')" :subheading="__('settings.password.description')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <flux:input
                wire:model="current_password"
                :label="__('fields.current_password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            <flux:input
                wire:model="password"
                :label="__('fields.new_password')"
                :description="__('fields.password_help')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />
            <flux:input
                wire:model="password_confirmation"
                :label="__('fields.password_confirmation')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full sm:w-auto" data-test="update-password-button">
                {{ __('actions.save') }}
            </flux:button>
        </form>
    </x-settings.layout>
</section>
