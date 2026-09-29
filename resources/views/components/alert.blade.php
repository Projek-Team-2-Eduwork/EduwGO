@props([
    'type' => 'info',   // success | error | warning | info
    'dismissible' => false,
])

@php
    $classes = match ($type) {
        'success' => 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-100',
        'error' => 'border-red-300 bg-red-50 text-red-900 dark:border-red-700 dark:bg-red-900/30 dark:text-red-100',
        'warning' => 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-100',
        default => 'border-sky-300 bg-sky-50 text-sky-900 dark:border-sky-700 dark:bg-sky-900/30 dark:text-sky-100',
    };
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    role="{{ $type === 'error' ? 'alert' : 'status' }}"
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-md border px-4 py-3 text-sm '.$classes]) }}
>
    <div class="flex-1">{{ $slot }}</div>

    @if ($dismissible)
        <button type="button" @click="show = false" class="-me-1 shrink-0 p-1 opacity-70 hover:opacity-100" aria-label="Tutup">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    @endif
</div>
