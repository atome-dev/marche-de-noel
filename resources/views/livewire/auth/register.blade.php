<div class="flex flex-col gap-6">
    <x-auth-header :title="__('registration.request.heading')" :description="__('registration.request.description')" />

    @if ($linkSent)
        <flux:callout variant="success" icon="envelope" data-test="registration-link-sent">
            <flux:callout.heading>{{ __('registration.request.sent_heading') }}</flux:callout.heading>
            <flux:callout.text>{{ __('registration.request.sent_text', ['email' => $email]) }}</flux:callout.text>
        </flux:callout>

        <flux:button wire:click="$set('linkSent', false)" variant="ghost" class="w-full">
            {{ __('registration.request.retry') }}
        </flux:button>
    @else
        <form wire:submit="submit" class="flex flex-col gap-6">
            <flux:input
                wire:model="email"
                :label="__('fields.email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                inputmode="email"
                placeholder="prenom.nom@exemple.fr"
            />

            <flux:input
                wire:model="temporaryPassword"
                :label="__('registration.request.temporary_password')"
                :description="__('registration.request.temporary_password_help')"
                type="password"
                required
                autocomplete="off"
                viewable
            />

            <flux:button type="submit" variant="primary" class="w-full" data-test="request-registration-button">
                {{ __('registration.request.submit') }}
            </flux:button>
        </form>
    @endif

    <div class="text-center text-sm text-zinc-600">
        <span>{{ __('registration.request.have_account') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('auth_pages.login.submit') }}</flux:link>
    </div>
</div>
