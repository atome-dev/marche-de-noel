<section class="mx-auto w-full max-w-2xl space-y-6">
    <div>
        <flux:link :href="route('cookies.index')" wire:navigate class="text-sm">← {{ __('cookies.index.heading') }}</flux:link>
        <flux:heading size="xl" level="1" class="mt-2 font-display">{{ __('cookies.categories.title') }}</flux:heading>
        <flux:subheading>{{ __('cookies.categories.description') }}</flux:subheading>
    </div>

    <form wire:submit="add" class="flex items-end gap-2">
        <div class="flex-1">
            <flux:input wire:model="name" :label="__('cookies.categories.new')" :placeholder="__('cookies.categories.placeholder')" />
        </div>
        <flux:button type="submit" variant="primary" icon="plus">{{ __('cookies.categories.add') }}</flux:button>
    </form>

    <div class="divide-y divide-zinc-200 overflow-hidden rounded-xl border border-zinc-200 bg-white">
        @forelse ($this->categories as $category)
            <div class="flex items-center gap-3 p-3" wire:key="category-{{ $category->id }}" x-data="{ name: @js($category->name) }">
                <div class="flex-1">
                    <flux:input size="sm" x-model="name" x-on:keydown.enter.prevent="$wire.rename({{ $category->id }}, name)" :aria-label="__('cookies.categories.name')" />
                </div>
                <span class="shrink-0 text-xs text-zinc-500">{{ trans_choice('cookies.categories.recipes_count', $category->recipes_count, ['count' => $category->recipes_count]) }}</span>
                <flux:button size="sm" variant="ghost" icon="check" x-on:click="$wire.rename({{ $category->id }}, name)" :aria-label="__('actions.save')" />
                <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $category->id }})"
                    wire:confirm="{{ __('cookies.categories.delete_confirm', ['name' => $category->name]) }}" :aria-label="__('actions.delete')" />
            </div>
        @empty
            <div class="p-6 text-center text-sm text-zinc-500">{{ __('cookies.categories.empty') }}</div>
        @endforelse
    </div>
</section>
