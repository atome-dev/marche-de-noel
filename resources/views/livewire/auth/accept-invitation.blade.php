<div class="flex flex-col gap-6">
    <x-auth-header :title="__('invitations.accept.heading')" :description="__('invitations.accept.description')" />

    <form wire:submit="accept" class="flex flex-col gap-6">
        <flux:input :label="__('fields.email')" :value="$email" type="email" readonly disabled />

        <div class="grid gap-6 sm:grid-cols-2">
            <flux:input wire:model="first_name" :label="__('fields.first_name')" required autofocus autocomplete="given-name" />
            <flux:input wire:model="last_name" :label="__('fields.last_name')" required autocomplete="family-name" />
        </div>

        <flux:input
            wire:model="phone"
            :label="__('fields.phone')"
            :description="__('fields.phone_help')"
            type="tel"
            autocomplete="tel"
            inputmode="tel"
        />

        @include('livewire.partials.profile-preferences')

        <flux:input
            wire:model="password"
            :label="__('fields.password')"
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

        <flux:button type="submit" variant="primary" class="w-full" data-test="accept-invitation-button">
            {{ __('invitations.accept.submit') }}
        </flux:button>
    </form>
</div>
