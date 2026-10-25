@props([
    'type' => 'info',   // success | error | warning | info
    'dismissible' => false,
])

@php
    $classes = match ($type) {
        'success' => 'border-[var(--line)] bg-[var(--success-soft)] text-[var(--success)] dark:border-[var(--line)] dark:bg-[var(--success-soft)] dark:text-[var(--success)]',
        'error' => 'border-[var(--line)] bg-[var(--danger-soft)] text-[var(--danger)] dark:border-[var(--line)] dark:bg-[var(--danger-soft)] dark:text-[var(--danger)]',
        'warning' => 'border-[var(--line)] bg-[var(--warning-soft)] text-[var(--warning)] dark:border-[var(--line)] dark:bg-[var(--warning-soft)] dark:text-[var(--warning)]',
        default => 'border-[var(--accent)] bg-[var(--info-soft)] text-[var(--info)] dark:border-[var(--accent)] dark:bg-[var(--info-soft)] dark:text-[var(--info)]',
    };
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    role="{{ $type === 'error' ? 'alert' : 'status' }}"
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm '.$classes]) }}
>
    <div class="flex-1">{{ $slot }}</div>

    @if ($dismissible)
        <button type="button" @click="show = false" class="-me-1 shrink-0 p-1 opacity-70 hover:opacity-100" aria-label="Tutup">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    @endif
</div>
