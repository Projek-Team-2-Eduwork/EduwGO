@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 4,
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

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->except('id')->merge(['class' => 'mt-1 block w-full rounded-md shadow-sm']) }}
        @error($errorKey) aria-invalid="true" @enderror
    >{{ old($errorKey, $value) }}</textarea>

    @if ($hint)
        <p class="mt-1 text-xs">{{ $hint }}</p>
    @endif

    <x-input-error :messages="$errors->get($errorKey)" class="mt-2" />
</div>
