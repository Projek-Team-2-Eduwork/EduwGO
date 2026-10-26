@props([
    'vehicle',
    'available' => null,   // status saat ini: true = bebas, false = sedang disewa, null = belum diketahui
    'scheduled' => false,  // true = lolos cek jadwal yang dipilih penyewa (rentang lengkap)
    'days' => null,        // durasi jadwal; total ditampilkan bila $scheduled
    'href' => null,        // default: halaman detail kendaraan
    'cta' => 'Lihat detail',
])

@php
    $href = $href ?? route('kendaraan.detail', $vehicle);
    $nextStart = $vehicle->next_booking_start;
    // Cabang hanya bila sudah di-eager-load (hindari N+1 di grid).
    $branchName = $vehicle->relationLoaded('branch') ? $vehicle->branch?->name : null;
    [$statusText, $statusOk] = match (true) {
        $scheduled => ['Tersedia untuk jadwal Anda', true],
        $available === false => ['Sedang disewa saat ini', false],
        default => ['Pilih jadwal untuk cek', null],
    };
@endphp

{{-- Seluruh kartu satu tautan; CTA hanya visual (span), tanpa elemen interaktif bersarang --}}
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'card-surface card-lift reveal group flex flex-col p-4']) }}>
    <div class="relative mb-4 flex aspect-[4/3] items-center justify-center overflow-hidden" aria-hidden="true">
        <x-illustration name="vehicles" class="absolute" />
        @if ($vehicle->image)
            <x-picture :src="$vehicle->image" alt="" loading="lazy" onerror="this.remove()" class="vehicle-card__image absolute inset-0 h-full w-full object-contain" />
        @endif
    </div>

    <h3 class="line-clamp-2 text-base font-bold">{{ $vehicle->name }}</h3>
    <p class="mt-0.5 text-xs">{{ collect([$vehicle->type?->name, $branchName ? 'Ambil di '.$branchName : null])->filter()->join(' · ') }}</p>

    <p @class(['mt-3 inline-flex items-center gap-1.5 text-xs font-semibold', 'text-[var(--success)]' => $statusOk, 'text-[var(--ink-muted)]' => ! $statusOk])>
        <span @class(['h-2 w-2 shrink-0 rounded-full', 'bg-current' => $statusOk !== null, 'border border-current' => $statusOk === null]) aria-hidden="true"></span>
        {{ $statusText }}
    </p>
    @if (! $scheduled && $nextStart?->isFuture())
        <p class="mt-1 flex items-center gap-1.5 text-xs">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="12" cy="12" r="9"></circle>
                <path stroke-linecap="round" d="M12 7v5l3 2"></path>
            </svg>
            <span>Dipesan berikutnya {{ $nextStart->translatedFormat('d M, H:i') }}</span>
        </p>
    @endif

    <div class="mt-auto flex items-end justify-between gap-3 pt-4">
        <p class="min-w-0">
            <span class="text-base font-bold text-[var(--ink)]">{{ rupiah((float) $vehicle->price_per_day) }}</span><span class="text-xs">/hari</span>
            @if ($scheduled && $days)
                <span class="block text-xs">{{ rupiah((float) $vehicle->price_per_day * $days) }} untuk {{ $days }} hari</span>
            @endif
        </p>

        <span class="btn-primary-edu inline-flex shrink-0 items-center px-3 text-xs">{{ $cta }}</span>
    </div>
</a>
