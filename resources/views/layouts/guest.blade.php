@php
    $namaBrand = setting('brand.name', 'EduwGo');
    $logoBrand = setting('brand.logo_dark') ?: setting('brand.logo_light');

    // Judul panel: pakai bagian tagline sebelum koma; bagian setelah "&" diberi warna aksen.
    $taglinePanel = setting('brand.tagline', 'Rental Motor Cepat & Aman');
    $judulPanel = trim(explode(',', $taglinePanel)[0]);
    $posisiAksen = strrpos($judulPanel, '&');
    $judulUtama = $posisiAksen === false ? $judulPanel : rtrim(substr($judulPanel, 0, $posisiAksen));
    $judulAksen = $posisiAksen === false ? '' : trim(substr($judulPanel, $posisiAksen));

    // Poin singkat — diambil dari teks yang sudah ada di halaman lain.
    $poinPanel = [
        'Unit terawat & siap jalan' => 'M5 17a2 2 0 104 0 2 2 0 00-4 0zm10 0a2 2 0 104 0 2 2 0 00-4 0zM7 17h8M3 17h2m14 0h2M6 9h6l3 5M12 9V6h3',
        'Bayar online: VA, QRIS, e-wallet' => 'M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2zm11 8h2',
        'Ambil langsung di lokasi' => 'M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z',
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head', ['title' => $title ?? null])
    </head>
    <body class="font-sans antialiased">
        {{-- Input ber-autofocus memicu browser auto-scroll ke form di layar kecil, sehingga panel
             merek terpotong saat halaman dibuka. Di bawah lg: lepas fokus + kembalikan ke atas.
             Di layar lg panel ada di kolom sendiri sehingga auto-scroll tidak perlu diutak-atik. --}}
        <div class="grid min-h-screen lg:grid-cols-[58fr_42fr]"
             x-data="{}"
             x-init="if (window.innerWidth < 1024) { document.activeElement && document.activeElement.blur(); window.scrollTo(0, 0); }">
            <!-- Panel merek: foto + gradasi navy, dipakai halaman masuk/daftar/lupa sandi -->
            <aside class="auth-panel relative flex min-h-[80vh] flex-col justify-between overflow-hidden px-6 py-8 sm:px-10 lg:min-h-screen lg:p-12"
                   style="background-image: url('{{ asset('images/auth/auth-toko.webp') }}');">
                <div class="auth-panel__latar" aria-hidden="true"></div>

                <a href="{{ url('/') }}" class="relative z-10 inline-flex w-fit items-center gap-2.5" aria-label="{{ $namaBrand }}">
                    @if ($logoBrand)
                        <img src="{{ $logoBrand }}" alt="{{ $namaBrand }}" class="h-9 w-auto">
                    @else
                        <span class="auth-panel__ikon" aria-hidden="true">E</span>
                        <span class="auth-panel__nama">Eduw<span class="auth-panel__nama-aksen">Go</span></span>
                    @endif
                </a>

                <div class="relative z-10 max-w-md">
                    <h2 class="auth-panel__judul">{{ $judulUtama }}@if ($judulAksen) <span class="auth-panel__aksen">{{ $judulAksen }}</span>@endif</h2>

                    <p class="auth-panel__teks mt-4">
                        Pilih motor, tentukan durasi, bayar online, lalu ambil di lokasi.
                    </p>

                    <ul class="auth-panel__poin mt-6">
                        @foreach ($poinPanel as $teksPoin => $ikonPoin)
                            <li>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $ikonPoin }}"/></svg>
                                {{ $teksPoin }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative z-10 text-xs text-white/55">
                    &copy; {{ now()->year }} {{ $namaBrand }}. Seluruh hak cipta dilindungi.
                </p>
            </aside>

            <!-- Form kanan -->
            <div class="flex flex-col">
                {{-- Bar atas ikut mengecil saat di-scroll, konsisten dengan halaman publik lain --}}
                <nav x-data="{ shrunk: false }"
                     x-init="shrunk = window.scrollY > 32"
                     x-on:scroll.window.passive="shrunk = window.scrollY > 32"
                     :class="{ 'is-shrunk': shrunk }"
                     class="navbar-edu navbar-edu--slim sticky top-0 z-40"
                     aria-label="Navigasi halaman akun">
                    <div class="navbar-edu-inner mx-auto w-full px-4 sm:px-8">
                        <div class="navbar-edu-bar flex items-center justify-between gap-4">
                            <a href="{{ url('/') }}" class="lg:invisible" aria-label="{{ $namaBrand }}">
                                <x-brand-logo class="h-9 w-auto" />
                            </a>

                            <x-theme-toggle />
                        </div>
                    </div>
                </nav>

                <div class="flex flex-1 items-center justify-center px-4 pb-10 sm:px-8">
                    <div class="w-full max-w-md">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
