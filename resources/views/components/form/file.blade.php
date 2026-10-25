@props(['name', 'label' => 'Foto', 'preview' => null, 'accept' => 'image/*'])
<div x-data="{ filename: '', preview: @js($preview), temporaryUrl: null }">
    <x-input-label :for="$name" :value="$label" />
    <div class="mt-2 flex flex-wrap items-center gap-3">
        <label for="{{ $name }}" class="btn-secondary-edu relative inline-flex cursor-pointer items-center">
            Pilih foto
            <input id="{{ $name }}" name="{{ $name }}" type="file" accept="{{ $accept }}" class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                @change="if (temporaryUrl) URL.revokeObjectURL(temporaryUrl); filename = $event.target.files[0]?.name || ''; temporaryUrl = $event.target.files[0]?.type.startsWith('image/') ? URL.createObjectURL($event.target.files[0]) : null; preview = temporaryUrl;" {{ $attributes }}>
        </label>
        <span class="text-sm" x-text="filename || 'Belum ada file'"></span>
    </div>
    <img x-show="preview" x-cloak :src="preview" alt="Pratinjau foto yang dipilih" class="mt-3 h-32 max-w-full rounded-lg object-contain">
    <x-input-error :messages="isset($errors) ? $errors->get($name) : []" class="mt-2" />
</div>
