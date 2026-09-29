@props([
    'vehicle',
    'available' => null,   // true = Tersedia, false = Disewa, null = badge disembunyikan
    'href' => null,        // default: halaman detail kendaraan
    'cta' => 'Booking',
])

@php
    $href = $href ?? route('kendaraan.detail', $vehicle);
@endphp

<article {{ $attributes->merge(['class' => 'card-surface flex flex-col overflow-hidden']) }}>
    {{-- Foto motor: latar surface (bukan putih hardcode), placeholder bila file belum ada --}}
    <a href="{{ $href }}" class="relative flex aspect-[4/3] items-center justify-center bg-[var(--bg)]" tabindex="-1" aria-hidden="true">
        <svg class="h-16 w-16 text-[var(--border)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17a2 2 0 104 0 2 2 0 00-4 0zm10 0a2 2 0 104 0 2 2 0 00-4 0zM7 17h8M3 17h2m14 0h2M6 9h6l3 5M12 9V6h3"/></svg>
        @if ($vehicle->image)
            <img src="{{ asset($vehicle->image) }}" alt="" loading="lazy" onerror="this.remove()" class="absolute inset-0 h-full w-full object-contain p-3">
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <h3 class="truncate font-serif text-xl">
                    <a href="{{ $href }}">{{ $vehicle->name }}</a>
                </h3>
                <p class="mt-0.5 text-sm">
                    {{ $vehicle->type?->name }}
                    @if ($vehicle->plate_number)
                        <span class="mx-1" aria-hidden="true">&middot;</span><span class="font-mono">{{ $vehicle->plate_number }}</span>
                    @endif
                </p>
            </div>

            @if (! is_null($available))
                <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $available ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-100' }}">
                    {{ $available ? 'Tersedia' : 'Disewa' }}
                </span>
            @endif
        </div>

        <div class="mt-4 flex items-end justify-between gap-3 pt-2">
            <p>
                <span class="text-lg font-semibold text-[var(--navy-900)]">Rp{{ number_format((float) $vehicle->price_per_day, 0, ',', '.') }}</span>
                <span class="text-sm">/hari</span>
            </p>

            <a href="{{ $href }}" @class([
                'btn-primary-edu inline-flex items-center px-4 py-2 text-sm',
                'pointer-events-none opacity-50' => $available === false,
            ]) @if ($available === false) aria-disabled="true" @endif>
                {{ $cta }}
            </a>
        </div>
    </div>
</article>
