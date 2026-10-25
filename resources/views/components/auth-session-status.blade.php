@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-[var(--success)] dark:text-[var(--success)]']) }}>
        {{ $status }}
    </div>
@endif
