<section class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:link :href="$recipeId ? route('cookies.show', $recipeId) : route('cookies.index')" wire:navigate class="text-sm">
            ← {{ $recipeId ? __('cookies.form.back_to_recipe') : __('cookies.index.heading') }}
        </flux:link>
        <flux:heading size="xl" level="1" class="mt-2 font-display">
            {{ $recipeId ? $title : __('cookies.form.create_title') }}
        </flux:heading>
    </div>

    <x-auth-session-status :status="session('status')" />

    <form wire:submit="save" class="space-y-6">
        <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-4 sm:p-6">
            <flux:input wire:model="title" :label="__('cookies.fields.title')" required />

            <flux:select wire:model="recipe_category_id" :label="__('cookies.fields.category')" :description="__('cookies.fields.category_help')">
                <flux:select.option value="">{{ __('cookies.fields.no_category') }}</flux:select.option>
                @foreach ($this->categories as $category)
                    <flux:select.option :value="$category->id" wire:key="category-{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:checkbox.group wire:model="schoolClassIds" :label="__('cookies.fields.classes')" :description="__('cookies.fields.classes_help')">
                <div class="mt-1 grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-3">
                    @foreach ($this->schoolClasses as $schoolClass)
                        <flux:checkbox :value="$schoolClass->id" :label="$schoolClass->name" wire:key="recipe-class-{{ $schoolClass->id }}" />
                    @endforeach
                </div>
            </flux:checkbox.group>
        </div>

        {{-- Ingrédients --}}
        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 sm:p-6">
            <div>
                <flux:heading size="lg" class="font-display">{{ __('cookies.show.ingredients') }}</flux:heading>
                <flux:subheading>{{ __('cookies.fields.ingredients_help') }}</flux:subheading>
            </div>

            @foreach ($ingredients as $index => $ingredient)
                <div class="space-y-3 rounded-lg bg-brand-sand/60 p-3" wire:key="ingredient-row-{{ $index }}">
                    <div class="grid grid-cols-12 gap-2">
                        <div class="col-span-12 sm:col-span-6">
                            <flux:input wire:model="ingredients.{{ $index }}.name" size="sm" :placeholder="__('cookies.fields.ingredient_name')" :aria-label="__('cookies.fields.ingredient_name')" />
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <flux:input wire:model="ingredients.{{ $index }}.quantity" size="sm" :placeholder="__('cookies.fields.ingredient_quantity')" :aria-label="__('cookies.fields.ingredient_quantity')" />
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <flux:input wire:model="ingredients.{{ $index }}.unit" size="sm" :placeholder="__('cookies.fields.ingredient_unit')" :aria-label="__('cookies.fields.ingredient_unit')" />
                        </div>
                        <div class="col-span-2 flex justify-end sm:col-span-1">
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeIngredient({{ $index }})" :aria-label="__('actions.delete')" />
                        </div>
                    </div>

                    @if ($this->orderables)
                        <div class="grid grid-cols-12 items-center gap-2">
                            <div class="col-span-12 sm:col-span-7">
                                <flux:select wire:model.live="ingredients.{{ $index }}.orderable_key" size="sm" :aria-label="__('cookies.fields.orderable')">
                                    <flux:select.option value="">{{ __('cookies.fields.not_orderable') }}</flux:select.option>
                                    @foreach ($this->orderables as $key => $name)
                                        <flux:select.option :value="$key" wire:key="orderable-{{ $index }}-{{ $key }}">{{ __('cookies.fields.orderable_option', ['ingredient' => $name]) }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            @if (filled($ingredient['orderable_key'] ?? ''))
                                <div class="col-span-12 flex items-center gap-2 sm:col-span-5">
                                    <div class="w-24 shrink-0">
                                        <flux:input wire:model="ingredients.{{ $index }}.grams_per_kg" size="sm" type="number" min="1" inputmode="numeric" :aria-label="__('cookies.fields.grams_per_kg')" />
                                    </div>
                                    <span class="text-xs text-zinc-600">{{ __('cookies.fields.grams_per_kg') }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    <flux:error name="ingredients.{{ $index }}.name" />
                    <flux:error name="ingredients.{{ $index }}.orderable_key" />
                    <flux:error name="ingredients.{{ $index }}.grams_per_kg" />
                </div>
            @endforeach

            <flux:button size="sm" icon="plus" wire:click="addIngredient">{{ __('cookies.fields.add_ingredient') }}</flux:button>
        </div>

        {{-- Instructions --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-4 sm:p-6">
            <flux:editor wire:model="instructions" :label="__('cookies.show.instructions')" toolbar="heading | bold italic underline | bullet ordered | link ~ undo redo" />
        </div>

        {{-- Photos, PDF et vidéo --}}
        <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-4 sm:p-6">
            <div class="space-y-3">
                <flux:input type="file" wire:model="newPhotos" multiple accept="image/jpeg,image/png,image/webp" :label="__('cookies.fields.photos')" :description="__('cookies.fields.photos_help')" />
                <flux:error name="newPhotos.*" />
                <div wire:loading wire:target="newPhotos" class="text-sm text-zinc-500">{{ __('cookies.fields.uploading') }}</div>

                @if ($this->recipe?->photos->isNotEmpty())
                    <div class="flex flex-wrap gap-3">
                        @foreach ($this->recipe->photos as $photo)
                            <div class="relative" wire:key="existing-photo-{{ $photo->id }}">
                                <img src="{{ $photo->url() }}" alt="" class="size-24 rounded-lg object-cover">
                                <flux:button size="xs" variant="danger" icon="x-mark" class="absolute! -right-2 -top-2"
                                    wire:click="deletePhoto({{ $photo->id }})" wire:confirm="{{ __('cookies.fields.delete_photo_confirm') }}" :aria-label="__('actions.delete')" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="space-y-3">
                <flux:input type="file" wire:model="pdfFile" accept="application/pdf" :label="__('cookies.fields.pdf_file')" />
                <div wire:loading wire:target="pdfFile" class="text-sm text-zinc-500">{{ __('cookies.fields.uploading') }}</div>

                @if ($this->recipe?->pdf_path)
                    <div class="flex items-center gap-3 text-sm">
                        <flux:link :href="$this->recipe->pdfLink()" target="_blank">{{ __('cookies.fields.current_pdf') }}</flux:link>
                        <flux:button size="xs" variant="ghost" icon="trash" wire:click="removePdf" wire:confirm="{{ __('cookies.fields.delete_pdf_confirm') }}">{{ __('actions.delete') }}</flux:button>
                    </div>
                @endif

                <flux:input wire:model="pdf_url" type="url" inputmode="url" :label="__('cookies.fields.pdf_url')" placeholder="https://…" />
            </div>

            <flux:input wire:model="video_url" type="url" inputmode="url" :label="__('cookies.fields.video_url')" :description="__('cookies.fields.video_url_help')" placeholder="https://www.youtube.com/watch?v=…" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:button type="submit" variant="primary" class="w-full sm:w-auto">{{ __('actions.save') }}</flux:button>

            @if ($recipeId)
                <flux:button variant="danger" icon="trash" wire:click="delete" wire:confirm="{{ __('cookies.form.delete_confirm', ['title' => $title]) }}">
                    {{ __('cookies.form.delete') }}
                </flux:button>
            @endif
        </div>
    </form>
</section>
