{{-- Langue et classes des enfants, partagés par l'inscription et le profil. --}}
@if (count(config('marche.locales')) > 1)
    <flux:select wire:model="locale" :label="__('fields.locale')">
        @foreach (config('marche.locales') as $availableLocale)
            <flux:select.option :value="$availableLocale" wire:key="locale-{{ $availableLocale }}">
                {{ __('locales.'.$availableLocale) }}
            </flux:select.option>
        @endforeach
    </flux:select>
@endif

<flux:checkbox.group wire:model="schoolClassIds" :label="__('fields.school_classes')" :description="__('fields.school_classes_help')">
    <div class="mt-1 grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-3">
        @foreach ($this->schoolClasses as $schoolClass)
            <flux:checkbox :value="$schoolClass->id" :label="$schoolClass->name" wire:key="class-{{ $schoolClass->id }}" />
        @endforeach
    </div>
</flux:checkbox.group>
<flux:error name="schoolClassIds.*" />
