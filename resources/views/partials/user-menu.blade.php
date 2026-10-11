<flux:menu>
    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
        <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
        <div class="grid flex-1 text-start text-sm leading-tight">
            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
        </div>
    </div>

    <flux:menu.separator />

    <flux:menu.item :href="route('profile.edit')" icon="user" wire:navigate>
        {{ __('navigation.profile') }}
    </flux:menu.item>

    <flux:menu.separator />

    <form method="POST" action="{{ route('logout') }}" class="w-full">
        @csrf
        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
            {{ __('navigation.logout') }}
        </flux:menu.item>
    </form>
</flux:menu>
