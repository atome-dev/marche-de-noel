<x-layouts::auth :title="__('auth_pages.reset.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth_pages.reset.title')" :description="__('auth_pages.reset.description')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <flux:input
                name="email"
                value="{{ old('email', request('email')) }}"
                :label="__('fields.email')"
                type="email"
                required
                autocomplete="email"
            />

            <flux:input
                name="password"
                :label="__('fields.new_password')"
                :description="__('fields.password_help')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:input
                name="password_confirmation"
                :label="__('fields.password_confirmation')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:button type="submit" variant="primary" class="w-full" data-test="reset-password-button">
                {{ __('auth_pages.reset.submit') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
