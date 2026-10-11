<x-layouts::auth :title="__('auth_pages.confirm.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth_pages.confirm.title')" :description="__('auth_pages.confirm.description')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                :label="__('fields.password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('actions.confirm') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
