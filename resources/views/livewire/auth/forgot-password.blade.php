<x-layouts::auth :title="__('auth_pages.forgot.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth_pages.forgot.title')" :description="__('auth_pages.forgot.description')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                :label="__('fields.email')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                inputmode="email"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('auth_pages.forgot.submit') }}
            </flux:button>
        </form>

        <div class="text-center text-sm text-zinc-600">
            <flux:link :href="route('login')" wire:navigate>{{ __('auth_pages.back_to_login') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
