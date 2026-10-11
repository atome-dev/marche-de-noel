<x-layouts::auth :title="__('auth_pages.login.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth_pages.login.heading')" :description="__('auth_pages.login.description')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
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

            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('fields.password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    viewable
                />

                <flux:link class="absolute end-0 top-0 text-sm" :href="route('password.request')" wire:navigate>
                    {{ __('auth_pages.login.forgot') }}
                </flux:link>
            </div>

            <flux:checkbox name="remember" :label="__('fields.remember')" :checked="old('remember', true)" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('auth_pages.login.submit') }}
            </flux:button>
        </form>

        <div class="text-center text-sm text-zinc-600">
            <span>{{ __('auth_pages.login.no_account') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('auth_pages.login.register') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
