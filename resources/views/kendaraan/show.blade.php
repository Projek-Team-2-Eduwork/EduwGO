<x-app-layout>
    {{-- Judul tab via slot `title` yang dibaca layouts.partials.head: "{nama} — EduwGo" --}}
    <x-slot:title>{{ $vehicle->name }}</x-slot:title>

    <div class="mx-auto max-w-[1320px] px-4 py-8 sm:px-6 lg:py-12 xl:px-0">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
            {{-- Kartu kiri: foto + spesifikasi (Figma: Desktop - Deskripsi Kendaraan) --}}
            <div class="card-surface p-5">
                <div class="relative flex aspect-[4/3] items-center justify-center">
                    <svg class="h-24 w-24 text-[var(--border)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 17a2 2 0 104 0 2 2 0 00-4 0zm10 0a2 2 0 104 0 2 2 0 00-4 0zM7 17h8M3 17h2m14 0h2M6 9h6l3 5M12 9V6h3"/></svg>
                    @if ($vehicle->image)
                        <img src="{{ asset($vehicle->image) }}" alt="Foto {{ $vehicle->name }}" onerror="this.remove()" class="absolute inset-0 h-full w-full object-contain">
                    @endif
                </div>

                <dl class="mt-4 space-y-2 text-sm">
                    @foreach ([
                        'Brand' => $vehicle->brand,
                        'Tipe' => $vehicle->type?->name,
                        'Kapasitas Tangki' => $vehicle->tank_capacity ? $vehicle->tank_capacity.' liter' : null,
                        'No. Plat' => $vehicle->plate_number,
                    ] as $label => $value)
                        <div class="flex justify-between gap-4">
                            <dt>{{ $label }}</dt>
                            <dd @class(['text-right font-semibold text-[var(--navy-900)]', 'font-mono' => $label === 'No. Plat'])>{{ $value ?: '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Kartu kanan: nama, status, deskripsi, harga + Booking --}}
            <div class="card-surface flex flex-col p-6">
                <div>
                    <h1 class="text-2xl text-[var(--navy-900)]">{{ $vehicle->name }}</h1>
                    <p class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold {{ $isAvailableNow ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-600 dark:text-slate-300' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $isAvailableNow ? 'bg-emerald-500' : 'bg-slate-400' }}" aria-hidden="true"></span>
                        {{ $isAvailableNow ? 'Tersedia' : 'Disewa' }}
                    </p>
                </div>

                <p class="mt-4 flex-1 text-sm leading-relaxed">{{ $vehicle->description ?: 'Deskripsi kendaraan belum tersedia.' }}</p>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-[var(--border)] pt-6">
                    <p>
                        <span class="text-2xl font-bold text-[var(--navy-900)]">Rp{{ number_format((float) $vehicle->price_per_day, 0, ',', '.') }}</span>
                        <span class="text-sm">/hari</span>
                    </p>

                    @auth
                        {{-- Komponen modal (EG-12) membawa tombol Booking sendiri yang membuka modal durasi --}}
                        <x-booking-duration-modal :vehicle="$vehicle" />
                    @else
                        <a href="{{ route('login') }}" class="btn-primary-edu inline-flex items-center justify-center px-6 py-2.5 text-sm">Booking</a>
                    @endauth
                </div>
            </div>
        </div>

        <section class="mt-12 lg:mt-16" aria-labelledby="rekomendasi-heading">
            <h2 id="rekomendasi-heading" class="text-2xl text-[var(--navy-900)]">Rekomendasi Kendaraan</h2>
            <p class="mt-2 text-sm">Pilih kendaraan yang paling sesuai dengan gaya perjalanan dan budget Anda. Semua motor terawat, siap jalan!</p>

            @if ($recommendations->isEmpty())
                <x-empty-state class="mt-6" title="Belum ada rekomendasi" description="Belum ada motor sejenis yang tersedia saat ini." action-label="Lihat Semua Kendaraan" :action-href="url('/kendaraan')" />
            @else
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($recommendations as $item)
                        <x-vehicle-card :vehicle="$item" :available="$filtered ? true : null" />
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
