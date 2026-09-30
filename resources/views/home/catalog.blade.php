<section id="katalog" class="mx-auto max-w-[1320px] px-4 pt-12 sm:px-6 lg:pt-20 xl:px-0">
    <div class="flex items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl">Pilihan Motor Kami</h2>
            <p class="mt-2 text-sm">Pilih kendaraan yang paling sesuai dengan gaya perjalanan dan budget Anda. Semua motor terawat, siap jalan!</p>
        </div>
        <a href="{{ url('/kendaraan') }}" class="shrink-0 text-sm font-semibold text-[var(--navy-900)] underline-offset-4 hover:underline">Lihat semua</a>
    </div>

    @if ($vehicles->isEmpty())
        <x-empty-state class="mt-6" title="Belum ada motor tersedia" description="Katalog motor akan segera diperbarui. Silakan kembali lagi nanti." />
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($vehicles as $vehicle)
                <x-vehicle-card :vehicle="$vehicle" :available="in_array($vehicle->id, $availableIds)" />
            @endforeach
        </div>
    @endif
</section>
