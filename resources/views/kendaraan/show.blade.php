<x-app-layout>
    {{-- Judul tab via slot `title` yang dibaca layouts.partials.head: "{nama} — EduwGo" --}}
    <x-slot:title>{{ $vehicle->name }}</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <nav class="mb-6 text-sm" aria-label="Breadcrumb">
            <a href="{{ url('/') }}" class="hover:underline">Beranda</a>
            <span class="mx-1" aria-hidden="true">/</span>
            <span class="text-[var(--navy-900)]">{{ $vehicle->name }}</span>
        </nav>

        <div class="grid gap-8 lg:grid-cols-2">
            {{-- Foto utama: latar token surface/bg, bukan putih hardcode --}}
            <div class="card-surface relative flex aspect-[4/3] items-center justify-center overflow-hidden bg-[var(--bg)]" style="background-color: var(--bg);">
                <svg class="h-24 w-24 text-[var(--border)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 17a2 2 0 104 0 2 2 0 00-4 0zm10 0a2 2 0 104 0 2 2 0 00-4 0zM7 17h8M3 17h2m14 0h2M6 9h6l3 5M12 9V6h3"/></svg>
                @if ($vehicle->image)
                    <img src="{{ asset($vehicle->image) }}" alt="Foto {{ $vehicle->name }}" onerror="this.remove()" class="absolute inset-0 h-full w-full object-contain p-6">
                @endif
            </div>

            <div class="flex flex-col">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h1 class="font-serif text-3xl text-[var(--navy-900)] sm:text-4xl">{{ $vehicle->name }}</h1>
                        @if ($vehicle->type)
                            <p class="mt-1 text-sm">{{ $vehicle->type->name }}</p>
                        @endif
                    </div>

                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $isAvailableNow ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-100' }}">
                        {{ $isAvailableNow ? 'Tersedia' : 'Disewa' }}
                    </span>
                </div>

                <h2 class="mt-6 font-serif text-xl text-[var(--navy-900)]">Spesifikasi</h2>
                <dl class="card-surface mt-3 divide-y divide-[var(--border)] text-sm">
                    @foreach ([
                        'Brand' => $vehicle->brand,
                        'Tipe' => $vehicle->type?->name,
                        'Kapasitas Tangki' => $vehicle->tank_capacity ? $vehicle->tank_capacity.' liter' : null,
                        'No. Plat' => $vehicle->plate_number,
                    ] as $label => $value)
                        <div class="flex justify-between gap-4 px-4 py-3">
                            <dt>{{ $label }}</dt>
                            <dd @class(['text-right font-semibold text-[var(--navy-900)]', 'font-mono' => $label === 'No. Plat'])>{{ $value ?: '-' }}</dd>
                        </div>
                    @endforeach
                </dl>

                <h2 class="mt-6 font-serif text-xl text-[var(--navy-900)]">Deskripsi</h2>
                <p class="mt-2 text-sm leading-relaxed">{{ $vehicle->description ?: 'Deskripsi kendaraan belum tersedia.' }}</p>

                <div class="mt-8 flex flex-wrap items-end justify-between gap-4 border-t border-[var(--border)] pt-6">
                    <p>
                        <span class="text-2xl font-semibold text-[var(--navy-900)]">Rp{{ number_format((float) $vehicle->price_per_day, 0, ',', '.') }}</span>
                        <span class="text-sm">/hari</span>
                    </p>

                    @auth
                        {{-- Komponen modal (EG-12) membawa tombol Booking sendiri yang membuka modal durasi --}}
                        <x-booking-duration-modal :vehicle="$vehicle" />
                    @else
                        <a href="{{ route('login') }}" class="btn-primary-edu inline-flex items-center justify-center px-5 py-2.5 text-sm">Booking</a>
                    @endauth
                </div>
            </div>
        </div>

        <section class="mt-16" aria-labelledby="rekomendasi-heading">
            <h2 id="rekomendasi-heading" class="font-serif text-2xl text-[var(--navy-900)]">Rekomendasi Kendaraan</h2>

            @if ($recommendations->isEmpty())
                <x-empty-state class="mt-6" title="Belum ada rekomendasi" description="Belum ada motor sejenis yang tersedia saat ini." action-label="Lihat Semua Kendaraan" :action-href="url('/kendaraan')" />
            @else
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($recommendations as $item)
                        <x-vehicle-card :vehicle="$item" :available="$filtered ? true : null" />
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
