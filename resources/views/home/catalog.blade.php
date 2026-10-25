<section id="katalog" class="landing-section landing-section--surface">
    <div class="landing-inner">
        <div class="flex items-baseline justify-between gap-4">
            <h2 class="reveal min-w-0 text-2xl">Motor di {{ $currentBranch?->name ?? 'cabang pilihan Anda' }}</h2>
            <a href="{{ route('kendaraan.index') }}" class="link-underline inline-flex min-h-11 shrink-0 items-center text-sm font-semibold text-[var(--ink)] underline-offset-4 hover:underline">Lihat semua</a>
        </div>
        <p class="mt-2 text-sm">{{ $activeVehicleCount }} motor aktif</p>

        @if ($vehicles->isEmpty())
            <x-empty-state class="mt-6" title="Belum ada motor tersedia" description="Katalog motor akan segera diperbarui. Silakan kembali lagi nanti." />
        @else
            <div class="vehicle-catalog-grid reveal-group mt-6">
                @foreach ($vehicles as $vehicle)
                    <x-vehicle-card :vehicle="$vehicle" :available="in_array($vehicle->id, $availableIds)" />
                @endforeach
            </div>
        @endif
    </div>
</section>
