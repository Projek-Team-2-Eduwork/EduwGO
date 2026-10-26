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
                    <a href="{{ route('kendaraan.detail', $vehicle) }}" class="home-motor reveal">
                        <div class="home-motor-photo">
                            @if ($vehicle->image)
                                <x-picture :src="$vehicle->image" :alt="$vehicle->name" width="640" height="480" loading="lazy" class="h-full w-full object-cover object-center" />
                            @else
                                <span class="text-sm">Foto belum tersedia</span>
                            @endif
                        </div>
                        <h3>{{ $vehicle->name }}</h3>
                        <p class="home-motor-type">{{ $vehicle->type?->name }}</p>
                        <p class="home-price"><strong>{{ rupiah((float) $vehicle->price_per_day) }}</strong><span>/hari</span></p>
                        @if(in_array($vehicle->id, $availableIds))
                            <p class="home-status is-available">{{ $vehicle->next_booking_start?->isFuture() ? 'Tersedia sampai '.$vehicle->next_booking_start->translatedFormat('d M, H:i') : 'Tersedia' }}</p>
                        @else
                            <p class="home-status">Sedang disewa</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
        <p class="home-catalog-note">{{ $activeVehicleCount }} motor aktif · Ketersediaan mengikuti jadwal sewa</p>
    </div>
</section>
