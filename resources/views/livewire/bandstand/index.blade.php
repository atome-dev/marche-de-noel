<section class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:heading size="xl" level="1" class="font-display">{{ __('bandstand.title') }}</flux:heading>
        <flux:subheading>{{ __('bandstand.description') }}</flux:subheading>
    </div>

    <div class="flex items-start gap-4 rounded-2xl border border-brand-line bg-white p-4 sm:p-6">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-sand text-brand-green">
            <flux:icon name="musical-note" />
        </span>
        <div class="space-y-3 text-sm text-zinc-700">
            @foreach (__('bandstand.presentation') as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
    </div>

    <div class="space-y-3">
        <div>
            <flux:heading size="lg" level="2" class="font-display">{{ __('bandstand.scores.heading') }}</flux:heading>
            <flux:subheading>{{ __('bandstand.scores.description') }}</flux:subheading>
        </div>

        <div class="divide-y divide-brand-line overflow-hidden rounded-2xl border border-brand-line bg-white">
            @forelse ($this->scores as $score)
                <div class="flex flex-wrap items-center gap-3 p-3" wire:key="score-{{ $score->id }}">
                    @if ($this->canManage && $renamingId === $score->id)
                        <form wire:submit="rename" class="flex w-full flex-wrap items-start gap-2">
                            <div class="min-w-0 flex-1 basis-56">
                                <flux:input wire:model="renamingTitle" :aria-label="__('bandstand.form.title')" maxlength="150" autofocus />
                                <flux:error name="renamingTitle" />
                            </div>
                            <flux:button type="submit" variant="primary" icon="check">{{ __('actions.save') }}</flux:button>
                            <flux:button type="button" variant="ghost" wire:click="cancelRenaming">{{ __('actions.cancel') }}</flux:button>
                        </form>
                    @else
                        <a href="{{ route('bandstand.scores.show', $score) }}" target="_blank" class="flex min-w-0 flex-1 basis-56 items-center gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-sand text-brand-green">
                                <flux:icon name="musical-note" />
                            </span>
                            <span class="min-w-0">
                                <span class="block font-semibold">{{ $score->title }}</span>
                                <span class="block text-xs text-zinc-500"><span class="uppercase">{{ $score->extension() }}</span> · {{ $score->sizeLabel() }}</span>
                            </span>
                        </a>

                        <div class="ms-auto flex items-center gap-1">
                            <flux:button size="sm" icon="arrow-top-right-on-square" :href="route('bandstand.scores.show', $score)" target="_blank">{{ __('bandstand.open') }}</flux:button>

                            @if ($this->canManage)
                                <flux:button size="sm" variant="ghost" icon="pencil" wire:click="startRenaming({{ $score->id }})" :aria-label="__('bandstand.form.rename')" />
                                <flux:button size="sm" variant="ghost" icon="chevron-up" wire:click="moveUp({{ $score->id }})" :disabled="$loop->first" :aria-label="__('bandstand.form.move_up')" />
                                <flux:button size="sm" variant="ghost" icon="chevron-down" wire:click="moveDown({{ $score->id }})" :disabled="$loop->last" :aria-label="__('bandstand.form.move_down')" />
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $score->id }})"
                                    wire:confirm="{{ __('bandstand.form.delete_confirm', ['title' => $score->title]) }}" :aria-label="__('actions.delete')" />
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-6 text-center text-sm text-zinc-500">{{ __('bandstand.empty') }}</div>
            @endforelse
        </div>
    </div>

    @if ($this->canManage)
        <form wire:submit="add" class="space-y-4 rounded-2xl border border-brand-line bg-white p-4 sm:p-6">
            <flux:heading size="lg" level="2" class="font-display">{{ __('bandstand.form.heading') }}</flux:heading>

            <flux:input wire:model="title" :label="__('bandstand.form.title')" :placeholder="__('bandstand.form.title_placeholder')" />

            <flux:input type="file" wire:model="file" :label="__('bandstand.form.file')"
                accept=".{{ implode(',.', \App\Livewire\Bandstand\Index::EXTENSIONS) }}"
                :description="__('bandstand.form.file_help', ['max' => $this->uploadLimit->megabytes()])" />
            <div wire:loading wire:target="file" class="text-sm text-zinc-500">{{ __('bandstand.form.uploading') }}</div>

            <flux:button type="submit" variant="primary" icon="plus" wire:loading.attr="disabled" wire:target="file">{{ __('bandstand.form.add') }}</flux:button>
        </form>
    @endif
</section>
