<section id="katalog" class="mx-auto max-w-7xl px-4 pt-16 sm:px-6 lg:px-8 lg:pt-24">
    <div class="flex items-end justify-between gap-4">
        <h2 class="font-serif text-3xl sm:text-4xl">Pilihan Motor Kami</h2>
        <a href="{{ url('/kendaraan') }}" class="btn-secondary-edu inline-flex shrink-0 items-center px-4 py-2 text-sm">Lihat semua</a>
    </div>

    @if ($vehicles->isEmpty())
        <x-empty-state class="mt-8" title="Belum ada motor tersedia" description="Katalog motor akan segera diperbarui. Silakan kembali lagi nanti." />
    @else
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($vehicles as $vehicle)
                <x-vehicle-card :vehicle="$vehicle" :available="in_array($vehicle->id, $availableIds)" />
            @endforeach
        </div>
    @endif
</section>
