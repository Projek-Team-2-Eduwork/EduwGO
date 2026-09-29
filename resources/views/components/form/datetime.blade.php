@props([
    'name',
    'label' => null,
    'value' => null,   // string/Carbon; diformat ke Y-m-d\TH:i untuk datetime-local
    'hint' => null,
])

@php
    $id = $attributes->get('id', $name);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $current = old($errorKey, $value);

    if ($current instanceof \DateTimeInterface) {
        $current = $current->format('Y-m-d\TH:i');
    }
@endphp

<div>
    @if ($label)
        <x-input-label :for="$id" :value="$label" />
    @endif

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="datetime-local"
        value="{{ $current }}"
        {{ $attributes->except('id')->merge(['class' => 'mt-1 block w-full rounded-md shadow-sm']) }}
        @error($errorKey) aria-invalid="true" @enderror
    >

    @if ($hint)
        <p class="mt-1 text-xs">{{ $hint }}</p>
    @endif

    <x-input-error :messages="$errors->get($errorKey)" class="mt-2" />
</div>
