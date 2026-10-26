<section id="katalog" class="landing-section">
    <div class="landing-inner">
        <div class="home-section-head">
            <h2>Motor tersedia di {{ $currentBranch?->name ?? 'cabang pilihan Anda' }}</h2>
            <a href="{{ route('kendaraan.index') }}" class="home-text-link">Lihat semua</a>
        </div>
        @if($types->count() > 1)
        <nav class="home-type-filters" aria-label="Filter tipe motor">
            <a href="{{ route('kendaraan.index', ['type' => '', 'cabang' => $currentBranch?->slug]) }}" class="is-selected">Semua</a>
            @foreach($types as $id => $name)
                <a href="{{ route('kendaraan.index', ['type' => $id, 'cabang' => $currentBranch?->slug]) }}">{{ $name }}</a>
            @endforeach
        </nav>
        @endif
        @if ($vehicles->isEmpty())
            <x-empty-state title="Belum ada motor tersedia" description="Silakan pilih cabang lain atau kembali lagi nanti." />
        @else
            <div class="home-motors" style="--motor-count: {{ $vehicles->count() <= 3 ? 3 : 4 }}">
                @foreach ($vehicles as $vehicle)
                    @php
                        $bookingUrl = route('kendaraan.detail', $vehicle).'#pesan';
                        $isAvailable = in_array($vehicle->id, $availableIds, true);
                    @endphp
                    {{-- Kartu yang sama dengan katalog; CTA Pesan tetap membawa pengguna ke form booking. --}}
                    <x-vehicle-card
                        :vehicle="$vehicle"
                        :available="$isAvailable"
                        :href="$bookingUrl"
                        cta="Pesan"
                    />
                @endforeach
            </div>
        @endif
        <p class="home-catalog-note">{{ $activeVehicleCount }} motor aktif · Ketersediaan mengikuti jadwal sewa</p>
    </div>
</section>
