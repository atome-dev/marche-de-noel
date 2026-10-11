<section class="mt-10 space-y-6">
    <flux:separator variant="subtle" />

    <div class="relative mb-5">
        <flux:heading>{{ __('settings.delete.heading') }}</flux:heading>
        <flux:subheading>{{ __('settings.delete.description') }}</flux:subheading>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button variant="danger">
            {{ __('settings.delete.action') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('settings.delete.confirm_heading') }}</flux:heading>
                <flux:subheading>{{ __('settings.delete.confirm_text') }}</flux:subheading>
            </div>

            <flux:input wire:model="password" :label="__('fields.password')" type="password" viewable />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('actions.cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit">{{ __('settings.delete.action') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
