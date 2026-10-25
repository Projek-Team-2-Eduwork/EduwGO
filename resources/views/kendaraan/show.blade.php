@php
    $meta = collect([$vehicle->type?->name, $vehicle->brand, $vehicle->branch ? 'Ambil di '.$vehicle->branch->name : null])->filter()->join(' · ');
    $price = rupiah((float) $vehicle->price_per_day);

    // Jadwal dari katalog (?start=&days=) untuk ringkasan tamu; input pengguna, di-parse hati-hati.
    $guestStart = rescue(fn () => filled(request('start')) ? \Illuminate\Support\Carbon::parse((string) request('start')) : null, null, false);
    $guestDays = max(0, (int) request('days'));

    // Rentang katalog tetap terbawa di tautan motor serupa.
    $saved = (array) session('booking.filter', []);
    $similarCarry = $filtered ? array_filter(['start' => $saved['start_at'] ?? null, 'days' => $saved['days'] ?? null], fn ($value) => filled($value)) : [];
@endphp

<x-app-layout>
    {{-- Judul tab via slot `title` yang dibaca layouts.partials.head: "{nama} — EduwGo" --}}
    <x-slot:title>{{ $vehicle->name }}</x-slot:title>

    {{-- Ruang untuk bar pesan bawah di mobile --}}
    <style>@media (max-width: 1023.98px) { body { padding-bottom: 5rem; } }</style>

    <div class="mx-auto max-w-[1320px] px-4 pt-6 sm:px-6 lg:pt-10 xl:px-0">
        <div class="flex flex-wrap items-center justify-between gap-x-4">
            <nav aria-label="Breadcrumb" class="flex min-w-0 items-center gap-2 text-sm">
                <a href="{{ route('home') }}" class="hidden min-h-11 items-center hover:underline sm:inline-flex">Beranda</a>
                <span class="hidden sm:inline" aria-hidden="true">/</span>
                <a href="{{ $backUrl }}" class="inline-flex min-h-11 shrink-0 items-center gap-1 hover:underline">
                    <svg class="h-4 w-4 sm:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Pilih motor
                </a>
                <span aria-hidden="true">/</span>
                <span aria-current="page" class="truncate text-[var(--ink)]">{{ $vehicle->name }}</span>
            </nav>
            <a href="{{ $backUrl }}" class="btn-secondary-edu btn-sm hidden min-h-11 items-center sm:inline-flex">Kembali ke hasil</a>
        </div>

        <h1 class="reveal mt-2 text-2xl text-[var(--ink)] lg:text-3xl">{{ $vehicle->name }}</h1>
        <p class="mt-1 text-sm">{{ $meta }}</p>

        <div class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start">
            <div class="min-w-0 space-y-8">
                {{-- Satu foto unit asli, utuh; tanpa galeri tiruan --}}
                <div @class(['relative flex min-h-48 items-center justify-center overflow-hidden rounded-lg border border-[var(--line)] bg-[var(--surface)]', 'aspect-[4/3]' => ! $vehicle->image])>
                    <x-illustration name="vehicles" class="absolute" />
                    @if ($vehicle->image)
                        <x-picture :src="$vehicle->image" alt="Foto {{ $vehicle->name }}" onerror="this.remove()" class="relative max-h-[520px] w-full object-contain" />
                    @endif
                </div>

                <section aria-labelledby="ringkasan-heading">
                    <h2 id="ringkasan-heading" class="text-lg text-[var(--ink)]">Ringkasan</h2>
                    <p @class(['mt-2 inline-flex items-center gap-1.5 text-sm font-semibold', 'text-[var(--success)]' => $isAvailableNow, 'text-[var(--ink-muted)]' => ! $isAvailableNow])>
                        <span class="h-2 w-2 rounded-full bg-current" aria-hidden="true"></span>
                        {{ $isAvailableNow ? 'Tersedia saat ini' : 'Sedang disewa saat ini' }}
                    </p>
                    <p class="mt-3 text-sm leading-relaxed">{{ $vehicle->description ?: 'Deskripsi kendaraan belum tersedia.' }}</p>

                    <ul class="mt-4 flex flex-wrap gap-2 text-sm" aria-label="Spesifikasi">
                        @foreach (array_filter([
                            'Merek' => $vehicle->brand,
                            'Tipe' => $vehicle->type?->name,
                            'Tangki' => $vehicle->tank_capacity ? $vehicle->tank_capacity.' liter' : null,
                        ]) as $label => $value)
                            <li class="inline-flex items-center gap-1.5 rounded-full border border-[var(--line)] px-3 py-1.5">
                                <span>{{ $label }}</span> <strong class="text-[var(--ink)]">{{ $value }}</strong>
                            </li>
                        @endforeach
                    </ul>

                    @if ($vehicle->plate_number)
                        <details class="group mt-3 text-sm">
                            <summary class="inline-flex min-h-11 cursor-pointer list-none items-center gap-1.5 font-medium text-[var(--ink)]">
                                <svg class="h-4 w-4 transition-transform group-open:rotate-90 motion-reduce:transition-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                Detail unit
                            </summary>
                            <p>No. plat <span class="font-mono font-semibold text-[var(--ink)]">{{ $vehicle->plate_number }}</span></p>
                        </details>
                    @endif
                </section>

                {{-- Kalender ketersediaan read-only (EG-41) hanya untuk tamu; user login memilih tanggal di kalender kartu pesan --}}
                @guest
                    <div class="border-t border-[var(--line)] pt-8"><x-availability-calendar :vehicle="$vehicle" :bare="true" /></div>
                @endguest

                <x-pickup-location :vehicle="$vehicle" class="border-t border-[var(--line)] pt-8" />

                <section class="border-t border-[var(--line)] pt-8" aria-labelledby="sk-heading">
                    <h2 id="sk-heading" class="text-lg text-[var(--ink)]">Syarat sewa singkat</h2>
                    <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-6">
                        @foreach ($terms as $term)
                            <li>{{ $term }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('terms') }}" class="mt-2 inline-flex min-h-11 items-center text-sm font-semibold underline">Baca seluruh ketentuan</a>
                </section>
            </div>

            {{-- Sticky dengan tinggi natural (tanpa scroll internal); layar terlalu pendek: ikut menggulir biasa --}}
            <aside class="lg:sticky lg:top-20 lg:[@media(max-height:719px)]:static" aria-label="Pesan motor">
                @auth
                    <x-booking-duration-modal :vehicle="$vehicle" />
                @else
                    <div class="hidden rounded-lg border border-[var(--line)] bg-[var(--surface)] p-6 shadow-sm lg:block">
                        <p><span class="text-2xl font-bold text-[var(--ink)]">{{ $price }}</span><span class="text-sm">/hari</span></p>
                        @if ($guestStart && $guestDays)
                            <dl class="receipt mt-4 border-t border-[var(--line)] pt-3 text-sm">
                                <div><dt>Mulai</dt><dd class="text-right font-semibold text-[var(--ink)]">{{ $guestStart->translatedFormat('d M Y, H:i') }}</dd></div>
                                <div><dt>Durasi</dt><dd class="text-right">{{ $guestDays }} hari</dd></div>
                                <div><dt class="font-semibold text-[var(--ink)]">Total sewa</dt><dd class="text-right font-bold text-[var(--ink)]">{{ rupiah((float) $vehicle->price_per_day * $guestDays) }}</dd></div>
                            </dl>
                        @endif
                        <p class="mt-4 text-sm">Masuk dulu untuk cek jadwal dan memesan. Anda kembali ke motor ini setelah masuk.</p>
                        <a href="{{ route('login') }}" class="btn-primary-edu mt-4 inline-flex w-full items-center justify-center">Masuk untuk pilih jadwal</a>
                        @if ($vehicle->branch)
                            <p class="mt-3 text-xs">Ambil di {{ $vehicle->branch->name }}</p>
                        @endif
                    </div>

                    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-[var(--line)] bg-[var(--surface)] px-4 pt-3 lg:hidden" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
                        <div class="flex items-center justify-between gap-3">
                            <p class="min-w-0 text-sm"><span class="text-lg font-bold text-[var(--ink)]">{{ $price }}</span>/hari</p>
                            <a href="{{ route('login') }}" class="btn-primary-edu inline-flex shrink-0 items-center">Masuk untuk pilih jadwal</a>
                        </div>
                    </div>
                @endauth
            </aside>
        </div>

        <section class="mt-12 lg:mt-16" aria-labelledby="serupa-heading">
            <h2 id="serupa-heading" class="reveal text-2xl text-[var(--ink)]">Motor serupa di cabang ini</h2>

            @if ($recommendations->isEmpty())
                <x-empty-state class="mt-6" title="Belum ada motor serupa" description="Belum ada motor sejenis lain di cabang ini." action-label="Lihat semua motor" :action-href="route('kendaraan.index')" />
            @else
                {{-- Kolom mengikuti jumlah kartu: 4 bila penuh, selain itu 3 (tanpa kolom kosong lebar) --}}
                <div @class(['reveal-group mt-6 grid gap-4 sm:grid-cols-2', 'lg:grid-cols-4' => $recommendations->count() >= 4, 'lg:grid-cols-3' => $recommendations->count() < 4])>
                    @foreach ($recommendations->take(4) as $item)
                        <x-vehicle-card :vehicle="$item" :available="! $item->is_rented" :scheduled="$filtered" :days="$similarCarry['days'] ?? null"
                            :href="route('kendaraan.detail', ['vehicle' => $item] + $similarCarry)" />
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
