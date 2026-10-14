<div class="space-y-3">
    <div>
        <flux:link :href="route('trees.index')" wire:navigate class="text-sm">← {{ __('trees.title') }}</flux:link>
        <flux:heading size="xl" level="1" class="mt-2 font-display">{{ __('trees.manage.title') }}</flux:heading>
    </div>

    <flux:navbar class="-mx-2 overflow-x-auto">
        <flux:navbar.item icon="clipboard-document-list" :href="route('trees.manage')" :current="$current === 'orders'" wire:navigate>{{ __('trees.manage.tabs.orders') }}</flux:navbar.item>
        <flux:navbar.item icon="table-cells" :href="route('trees.summary')" :current="$current === 'summary'" wire:navigate>{{ __('trees.manage.tabs.summary') }}</flux:navbar.item>
        <flux:navbar.item icon="check-badge" :href="route('trees.pickup')" :current="$current === 'pickup'" wire:navigate>{{ __('trees.manage.tabs.pickup') }}</flux:navbar.item>
    </flux:navbar>
</div>
