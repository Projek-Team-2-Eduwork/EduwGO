@props(['active' => false])

@php
$classes = $active
            ? 'inline-flex items-center text-sm font-bold leading-5 text-[var(--ink)] focus:outline-none focus-visible:underline transition duration-150 ease-in-out'
            : 'inline-flex items-center text-sm font-medium leading-5 text-[var(--ink-muted)] hover:text-[var(--ink)] focus:outline-none focus-visible:text-[var(--ink)] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active) aria-current="page" @endif>
    {{ $slot }}
</a>
