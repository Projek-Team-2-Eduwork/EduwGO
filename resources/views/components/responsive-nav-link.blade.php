@props(['active' => false])

@php
$classes = $active
            ? 'block w-full border-l-4 border-[var(--orange-500)] bg-[var(--bg)] py-2 ps-3 pe-4 text-start text-base font-medium text-[var(--navy-900)] focus:outline-none transition duration-150 ease-in-out'
            : 'block w-full border-l-4 border-transparent py-2 ps-3 pe-4 text-start text-base font-medium text-[var(--ink-muted)] hover:border-[var(--border)] hover:bg-[var(--bg)] hover:text-[var(--navy-900)] focus:outline-none focus-visible:bg-[var(--bg)] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active) aria-current="page" @endif>
    {{ $slot }}
</a>
