<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl" level="1" class="font-display">{{ __('admin.users.heading') }}</flux:heading>
        <flux:subheading>{{ trans_choice('admin.users.count', $this->users->total(), ['count' => $this->users->total()]) }}</flux:subheading>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('admin.users.search')" clearable />

    <div class="divide-y divide-zinc-200 overflow-hidden rounded-xl border border-zinc-200 bg-white">
        @forelse ($this->users as $user)
            <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between" wire:key="user-{{ $user->id }}">
                <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold">{{ $user->name }}</span>
                        @if ($user->isAdmin())
                            <flux:badge size="sm" color="amber">{{ $user->role->label() }}</flux:badge>
                        @endif
                    </div>
                    <div class="flex flex-col gap-0.5 text-sm text-zinc-600 sm:flex-row sm:gap-4">
                        <a href="mailto:{{ $user->email }}" class="truncate hover:underline">{{ $user->email }}</a>
                        @if ($user->phone)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $user->phone) }}" class="hover:underline">{{ $user->phone }}</a>
                        @endif
                    </div>
                    @if ($user->schoolClasses->isNotEmpty())
                        <div class="flex flex-wrap gap-1 pt-1">
                            @foreach ($user->schoolClasses as $schoolClass)
                                <flux:badge size="sm" wire:key="user-{{ $user->id }}-class-{{ $schoolClass->id }}">{{ $schoolClass->name }}</flux:badge>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($user->id !== auth()->id())
                    <div class="flex shrink-0 items-center gap-2">
                        <flux:select
                            size="sm"
                            class="min-w-36"
                            :aria-label="__('fields.role')"
                            wire:change="changeRole({{ $user->id }}, $event.target.value)"
                        >
                            @foreach (\App\Enums\Role::cases() as $role)
                                <flux:select.option :value="$role->value" :selected="$user->role === $role">{{ $role->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            :aria-label="__('admin.users.delete')"
                            wire:click="deleteUser({{ $user->id }})"
                            wire:confirm="{{ __('admin.users.delete_confirm', ['name' => $user->name]) }}"
                        />
                    </div>
                @endif
            </div>
        @empty
            <div class="p-8 text-center text-zinc-500">{{ __('admin.users.empty') }}</div>
        @endforelse
    </div>

    {{ $this->users->links() }}
</section>
