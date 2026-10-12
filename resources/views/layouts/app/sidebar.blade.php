<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-brand-cream font-sans text-brand-ink antialiased">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-brand-line bg-brand-sand">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('navigation.home') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="calendar-days" :href="route('planning')" :current="request()->routeIs('planning')" wire:navigate>
                    {{ __('navigation.planning') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="cake" :href="route('cookies.index')" :current="request()->routeIs('cookies.*')" wire:navigate>
                    {{ __('navigation.cookies') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="gift" :href="route('raffle.index')" :current="request()->routeIs('raffle.*')" wire:navigate>
                    {{ __('navigation.raffle') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="document-text" :href="route('documents.index')" :current="request()->routeIs('documents.*')" wire:navigate>
                    {{ __('navigation.documents') }}
                </flux:sidebar.item>
                @if (auth()->user()->isAdmin() || auth()->user()->managedStands()->exists())
                    <flux:sidebar.item icon="building-storefront" :href="route('stands.index')" :current="request()->routeIs('stands.*')" wire:navigate>
                        {{ auth()->user()->isAdmin() ? __('navigation.stands') : __('navigation.my_stands') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.nav>

            @can('admin')
                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('navigation.administration')" class="grid">
                        <flux:sidebar.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>
                            {{ __('navigation.users') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="circle-stack" :href="route('admin.snapshots')" :current="request()->routeIs('admin.snapshots')" wire:navigate>
                            {{ __('navigation.snapshots') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcan

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="globe-alt" :href="config('marche.school_url')" target="_blank">
                    {{ __('navigation.school_site') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="shield-check" :href="route('privacy')" :current="request()->routeIs('privacy')">
                    {{ __('privacy.link') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" />
        </flux:sidebar>

        <!-- Mobile header -->
        <flux:header class="border-b border-brand-line bg-brand-sand lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" :aria-label="__('navigation.menu')" />

            <a href="{{ route('dashboard') }}" class="ms-2 flex min-w-0 items-center gap-2" wire:navigate>
                <x-app-logo-icon class="size-7" />
                <span class="truncate font-display text-lg">{{ config('marche.name') }}</span>
            </a>

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

                @include('partials.user-menu')
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
