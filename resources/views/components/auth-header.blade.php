@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-1 text-center">
    <flux:heading size="xl" level="1" class="font-display text-2xl! font-bold! text-brand-ink">{{ $title }}</flux:heading>
    <flux:subheading>{{ $description }}</flux:subheading>
</div>
