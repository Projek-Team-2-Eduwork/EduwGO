@props(['status'])

@php
    use App\Enums\BookingStatus;

    $status = $status instanceof BookingStatus ? $status : BookingStatus::from($status);

    // Kontras dicek AA di terang & gelap: teks selalu memakai varian pekat/terang sesuai tema.
    $classes = match ($status->color()) {
        'success' => 'bg-[var(--success-soft)] text-[var(--success)] dark:bg-[var(--success-soft)] dark:text-[var(--success)]',
        'warning' => 'bg-[var(--warning-soft)] text-[var(--warning)] dark:bg-[var(--warning-soft)] dark:text-[var(--warning)]',
        'danger' => 'bg-[var(--danger-soft)] text-[var(--danger)] dark:bg-[var(--danger-soft)] dark:text-[var(--danger)]',
        'info' => 'bg-[var(--info-soft)] text-[var(--info)] dark:bg-[var(--info-soft)] dark:text-[var(--info)]',
        default => 'bg-[var(--bg)] text-[var(--ink)] dark:bg-[var(--bg)] dark:text-[var(--ink-muted)]',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold '.$classes]) }}>
    {{ $status->label() }}
</span>
