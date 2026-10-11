<flux:dropdown position="bottom" align="start" {{ $attributes }}>
    <flux:sidebar.profile
        :name="auth()->user()->name"
        :initials="auth()->user()->initials()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
    />

    @include('partials.user-menu')
</flux:dropdown>
