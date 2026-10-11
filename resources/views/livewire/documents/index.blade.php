<section class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:heading size="xl" level="1" class="font-display">{{ __('documents.title') }}</flux:heading>
        <flux:subheading>{{ __('documents.description') }}</flux:subheading>
    </div>

    <div class="divide-y divide-brand-line overflow-hidden rounded-2xl border border-brand-line bg-white">
        @forelse ($this->documents as $document)
            <div class="flex flex-wrap items-center gap-3 p-3" wire:key="document-{{ $document->id }}">
                <a href="{{ route('documents.show', $document) }}" target="_blank" class="flex min-w-0 flex-1 basis-56 items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-sand text-brand-green">
                        <flux:icon name="document-text" />
                    </span>
                    <span class="min-w-0">
                        <span class="block font-semibold">{{ $document->title }}</span>
                        <span class="block text-xs text-zinc-500"><span class="uppercase">{{ $document->extension() }}</span> · {{ $document->sizeLabel() }}</span>
                    </span>
                </a>

                <div class="ms-auto flex items-center gap-1">
                    <flux:button size="sm" icon="arrow-top-right-on-square" :href="route('documents.show', $document)" target="_blank">{{ __('documents.open') }}</flux:button>

                    @if ($this->canManage)
                        <flux:button size="sm" variant="ghost" icon="chevron-up" wire:click="moveUp({{ $document->id }})" :disabled="$loop->first" :aria-label="__('documents.form.move_up')" />
                        <flux:button size="sm" variant="ghost" icon="chevron-down" wire:click="moveDown({{ $document->id }})" :disabled="$loop->last" :aria-label="__('documents.form.move_down')" />
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $document->id }})"
                            wire:confirm="{{ __('documents.form.delete_confirm', ['title' => $document->title]) }}" :aria-label="__('actions.delete')" />
                    @endif
                </div>
            </div>
        @empty
            <div class="p-6 text-center text-sm text-zinc-500">{{ __('documents.empty') }}</div>
        @endforelse
    </div>

    @if ($this->canManage)
        <form wire:submit="add" class="space-y-4 rounded-2xl border border-brand-line bg-white p-4 sm:p-6">
            <flux:heading size="lg" level="2" class="font-display">{{ __('documents.form.heading') }}</flux:heading>

            <flux:input wire:model="title" :label="__('documents.form.title')" :placeholder="__('documents.form.title_placeholder')" />

            <flux:input type="file" wire:model="file" :label="__('documents.form.file')"
                accept=".{{ implode(',.', \App\Livewire\Documents\Index::EXTENSIONS) }}"
                :description="__('documents.form.file_help', ['max' => config('marche.documents.max_size_mb')])" />
            <div wire:loading wire:target="file" class="text-sm text-zinc-500">{{ __('documents.form.uploading') }}</div>

            <flux:button type="submit" variant="primary" icon="plus" wire:loading.attr="disabled" wire:target="file">{{ __('documents.form.add') }}</flux:button>
        </form>
    @endif
</section>
