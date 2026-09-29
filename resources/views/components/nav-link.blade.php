@props(['active' => false])

@php
$classes = $active
            ? 'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 border-[var(--orange-500)] text-[var(--navy-900)] focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-[var(--ink-muted)] hover:border-[var(--border)] hover:text-[var(--navy-900)] focus:outline-none focus-visible:text-[var(--navy-900)] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active) aria-current="page" @endif>
    {{ $slot }}
</a>
