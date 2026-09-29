@props(['value' => null])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-[var(--navy-900)]']) }}>
    {{ $value ?? $slot }}
</label>
