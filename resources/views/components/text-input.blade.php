@props(['disabled' => false, 'type' => 'text'])

<input type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg shadow-sm']) }}>
