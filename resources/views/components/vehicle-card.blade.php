@props([
    'vehicle',
    'available' => null,   // true = Tersedia, false = Disewa, null = badge disembunyikan
    'href' => null,        // default: halaman detail kendaraan
    'cta' => 'Booking',
])

@php
    $href = $href ?? route('kendaraan.detail', $vehicle);
@endphp

<article {{ $attributes->merge(['class' => 'card-surface group flex flex-col p-4 transition duration-150 hover:-translate-y-0.5 hover:shadow-md']) }}>
    <div class="min-w-0">
        <h3 class="truncate text-base font-bold">
            <a href="{{ $href }}">{{ $vehicle->name }}</a>
        </h3>
        <p class="mt-0.5 text-xs">{{ $vehicle->type?->name }}</p>
    </div>

    {{-- Foto motor: latar surface (bukan putih hardcode), placeholder bila file belum ada --}}
    <a href="{{ $href }}" class="relative my-3 flex aspect-[4/3] items-center justify-center" tabindex="-1" aria-hidden="true">
        <svg class="h-16 w-16 text-[var(--border)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17a2 2 0 104 0 2 2 0 00-4 0zm10 0a2 2 0 104 0 2 2 0 00-4 0zM7 17h8M3 17h2m14 0h2M6 9h6l3 5M12 9V6h3"/></svg>
        @if ($vehicle->image)
            <img src="{{ asset($vehicle->image) }}" alt="" loading="lazy" onerror="this.remove()" class="absolute inset-0 h-full w-full object-contain">
        @endif
    </a>

    <div class="flex items-center justify-between gap-2 text-xs">
        <span class="inline-flex min-w-0 items-center gap-1.5">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="7" width="18" height="10" rx="2"/><path d="M7 11h.01M11 11h6"/></svg>
            <span class="truncate font-mono">{{ $vehicle->plate_number ?: '-' }}</span>
        </span>

        @if (! is_null($available))
            <span class="inline-flex shrink-0 items-center gap-1.5 font-semibold {{ $available ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-600 dark:text-slate-300' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $available ? 'bg-emerald-500' : 'bg-slate-400' }}" aria-hidden="true"></span>
                {{ $available ? 'Tersedia' : 'Disewa' }}
            </span>
        @endif
    </div>

    <div class="mt-3 flex items-center justify-between gap-3">
        <p class="min-w-0">
            <span class="text-base font-bold text-[var(--navy-900)]">Rp{{ number_format((float) $vehicle->price_per_day, 0, ',', '.') }}</span><span class="text-xs">/hari</span>
        </p>

        <a href="{{ $href }}" @class([
            'btn-primary-edu inline-flex shrink-0 items-center px-4 py-2 text-xs',
            'pointer-events-none opacity-50' => $available === false,
        ]) @if ($available === false) aria-disabled="true" @endif>
            {{ $cta }}
        </a>
    </div>
</article>
