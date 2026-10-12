<section class="w-full space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1" class="font-display">{{ __('admin.users.heading') }}</flux:heading>
            <flux:subheading>{{ trans_choice('admin.users.count', $this->users->total(), ['count' => $this->users->total()]) }}</flux:subheading>
        </div>

        <flux:modal.trigger name="invite">
            <flux:button variant="primary" icon="envelope">{{ __('invitations.admin.invite') }}</flux:button>
        </flux:modal.trigger>
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
                        @if ($user->email_verified_at === null)
                            <flux:badge size="sm" color="sky" icon="envelope">{{ __('invitations.admin.pending') }}</flux:badge>
                        @endif
                    </div>
                    <div class="flex flex-col gap-0.5 text-sm text-zinc-600 sm:flex-row sm:gap-4">
                        <a href="mailto:{{ $user->email }}" class="truncate hover:underline">{{ $user->email }}</a>
                        @if ($user->phone)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $user->phone) }}" class="hover:underline">{{ $user->phone }}</a>
                        @endif
                    </div>
                    @if ($user->managedStands->isNotEmpty())
                        <div class="flex min-w-0 flex-wrap items-center gap-1 pt-1">
                            <span class="text-xs text-zinc-500">{{ trans_choice('admin.users.manages', $user->managedStands->count()) }}</span>
                            @foreach ($user->managedStands as $stand)
                                <flux:badge size="sm" color="teal" icon="building-storefront" as="a" :href="route('stands.edit', $stand)" wire:navigate wire:key="user-{{ $user->id }}-stand-{{ $stand->id }}" class="max-w-full" :title="$stand->name"><span class="truncate">{{ $stand->name }}</span></flux:badge>
                            @endforeach
                        </div>
                    @endif
                    @if ($user->schoolClasses->isNotEmpty())
                        <div class="flex flex-wrap gap-1 pt-1">
                            @foreach ($user->schoolClasses as $schoolClass)
                                <flux:badge size="sm" wire:key="user-{{ $user->id }}-class-{{ $schoolClass->id }}">{{ $schoolClass->name }}</flux:badge>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($user->id !== auth()->id())
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @if ($user->email_verified_at === null)
                            <flux:button size="sm" icon="paper-airplane" wire:click="resendInvitation({{ $user->id }})">{{ __('invitations.admin.resend') }}</flux:button>
                        @endif

                        @unless ($user->isAdmin())
                            <flux:button size="sm" icon="eye" wire:click="impersonate({{ $user->id }})">{{ __('admin.impersonation.start') }}</flux:button>
                        @endunless

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

    <flux:modal name="invite" class="w-full max-w-lg">
        <form wire:submit="invite" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('invitations.admin.heading') }}</flux:heading>
                <flux:subheading>{{ __('invitations.admin.description') }}</flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="inviteFirstName" :label="__('fields.first_name')" required />
                <flux:input wire:model="inviteLastName" :label="__('fields.last_name')" required />
            </div>

            <flux:input wire:model="inviteEmail" type="email" :label="__('fields.email')" required />

            <flux:select wire:model="inviteStandIds" variant="listbox" multiple searchable
                :label="__('invitations.admin.stands')" :placeholder="__('invitations.admin.stands_placeholder')">
                <x-slot name="search">
                    <flux:select.search :placeholder="__('invitations.admin.stands_search')" />
                </x-slot>

                @foreach ($this->stands as $stand)
                    <flux:select.option :value="$stand->id" wire:key="invite-stand-{{ $stand->id }}">{{ $stand->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model="inviteMessage" rows="3" :label="__('invitations.admin.message')" :description="__('invitations.admin.message_help')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('actions.cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="paper-airplane">{{ __('invitations.admin.send') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
