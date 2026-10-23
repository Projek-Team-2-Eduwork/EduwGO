@props(['status'])

@php
    use App\Enums\BookingStatus;

    $status = $status instanceof BookingStatus ? $status : BookingStatus::from($status);

    // Kontras dicek AA di terang & gelap: teks selalu memakai varian pekat/terang sesuai tema.
    $classes = match ($status->color()) {
        'success' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
        'warning' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
        'danger' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
        'info' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
        default => 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-100',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold '.$classes]) }}>
    {{ $status->label() }}
</span>
