@props([
    'name',
    'label' => null,
    'options' => [],       // [value => label]
    'value' => null,
    'placeholder' => null, // teks opsi kosong (mis. "Semua tipe")
    'hint' => null,
])

@php
    $id = $attributes->get('id', $name);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $selected = (string) old($errorKey, $value);
@endphp

<div>
    @if ($label)
        <x-input-label :for="$id" :value="$label" />
    @endif

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        {{ $attributes->except('id')->merge(['class' => 'mt-1 block w-full rounded-md shadow-sm']) }}
        @error($errorKey) aria-invalid="true" @enderror
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @if ($hint)
        <p class="mt-1 text-xs">{{ $hint }}</p>
    @endif

    <x-input-error :messages="$errors->get($errorKey)" class="mt-2" />
</div>
