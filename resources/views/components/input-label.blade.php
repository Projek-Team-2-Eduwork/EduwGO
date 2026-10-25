@props(['value' => null])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-[var(--ink)]']) }}>
    {{ $value ?? $slot }}
</label>
