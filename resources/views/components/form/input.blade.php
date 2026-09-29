@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
])

@php
    $id = $attributes->get('id', $name);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
@endphp

<div>
    @if ($label)
        <x-input-label :for="$id" :value="$label" />
    @endif

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($errorKey, $value) }}"
        {{ $attributes->except('id')->merge(['class' => 'mt-1 block w-full rounded-md shadow-sm']) }}
        @error($errorKey) aria-invalid="true" @enderror
    >

    @if ($hint)
        <p class="mt-1 text-xs">{{ $hint }}</p>
    @endif

    <x-input-error :messages="$errors->get($errorKey)" class="mt-2" />
</div>
