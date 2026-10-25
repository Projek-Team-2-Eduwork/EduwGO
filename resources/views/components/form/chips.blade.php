@props([
    'name',
    'label' => null,
    'options' => [],       // [value => label]
    'value' => null,
    'placeholder' => 'Semua', // label chip "tanpa filter" (value kosong); null = chip disembunyikan
])

@php
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $selected = (string) old($errorKey, $value);
@endphp

{{-- Chip pilihan tunggal berbasis radio: dikirim sebagai query ?name=value, sama seperti <select> --}}
<fieldset {{ $attributes }}>
    @if ($label)
        <legend class="block text-sm font-medium text-[var(--ink)]">{{ $label }}</legend>
    @endif

    <div class="mt-1 flex flex-wrap gap-2">
        @if ($placeholder !== null)
            <label class="chip-option">
                <input type="radio" name="{{ $name }}" value="" @checked($selected === '')>
                <span class="chip-face">{{ $placeholder }}</span>
            </label>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <label class="chip-option">
                <input type="radio" name="{{ $name }}" value="{{ $optionValue }}" @checked($selected === (string) $optionValue)>
                <span class="chip-face">{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>

    <x-input-error :messages="$errors->get($errorKey)" class="mt-2" />
</fieldset>
