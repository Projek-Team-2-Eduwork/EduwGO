@php
    $brand = setting('brand.name', 'EduwGo');
    $waNumber = preg_replace('/\D+/', '', (string) setting('contact.whatsapp', ''));
    // Nomor belum diatur: arahkan ke wa.me tanpa nomor agar tidak menghasilkan tautan rusak.
    $waUrl = $waNumber ? 'https://wa.me/'.$waNumber.'?text='.rawurlencode('Halo, saya butuh bantuan.') : '//wa.me';

    // Alamat, jam, kontak & sosmed per cabang aktif (cabang baru otomatis muncul)
    $branches = app(\App\Services\BranchContext::class)->active();

    // Sosmed global: hanya yang benar-benar diatur (placeholder '#' tidak ditampilkan)
    $socials = array_filter([
        'Instagram' => setting('social.instagram', ''),
        'Facebook' => setting('social.facebook', ''),
        'TikTok' => setting('social.tiktok', ''),
        'YouTube' => setting('social.youtube', ''),
    ], fn ($href) => filled($href) && $href !== '#');

    // Ikon sosmed (isi saja; viewBox & atribut dipasang di markup)
    $socialIcons = [
        'Instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5" /><circle cx="12" cy="12" r="3.8" /><circle cx="16.8" cy="7.2" r="1.1" fill="currentColor" stroke="none" />',
        'Facebook' => '<path d="M15 3.5h-2.2A3.8 3.8 0 009 7.3V9.5H6.6v3H9v8h3.1v-8h2.3l.5-3h-2.8V7.6c0-.6.4-1 1-1H15z" fill="currentColor" stroke="none" />',
        'TikTok' => '<path d="M10 18.5V6.2l9-1.7v12" /><circle cx="7.2" cy="18.4" r="2.8" /><circle cx="16.2" cy="16.5" r="2.8" />',
        'YouTube' => '<rect x="2.5" y="5.5" width="19" height="13" rx="4" /><path d="M10.8 9.3l4.4 2.7-4.4 2.7z" fill="currentColor" stroke="none" />',
    ];

    // Kolom navigasi
    $navLinks = [
        ['label' => 'Tentang Kami', 'href' => route('about'), 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Cara Sewa', 'href' => url('/#cara-sewa'), 'icon' => 'M9 5h6M9 9h6m-6 4h6M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z'],
        ['label' => 'Syarat & Ketentuan', 'href' => route('terms'), 'icon' => 'M12 3l7 4v5.5c0 4.4-2.9 7.6-7 8.5-4.1-.9-7-4.1-7-8.5V7z'],
        ['label' => 'Pilih Kendaraan', 'href' => url('/kendaraan'), 'icon' => 'M5 17a2 2 0 104 0 2 2 0 00-4 0zm10 0a2 2 0 104 0 2 2 0 00-4 0zM7 17h8M3 17h2m14 0h2M6 9h6l3 5M12 9V6h3'],
        ['label' => 'Daftar Pesanan', 'href' => url('/pesanan'), 'icon' => 'M4 6h16M4 12h16M4 18h10'],
    ];
@endphp

<footer class="mt-20 border-t border-[var(--border)]" style="background-color: var(--surface);">
    <div class="mx-auto max-w-[1320px] px-4 py-12 sm:px-6 lg:py-14 xl:px-0">
        {{-- Baris atas: merek + navigasi, ditengahkan --}}
        <div class="flex flex-col gap-10 lg:flex-row lg:justify-center lg:gap-24">
            <div class="lg:max-w-sm">
                <x-brand-logo class="h-9 w-auto" />

                <p class="mt-4 max-w-sm text-sm leading-relaxed">
                    {{ setting('brand.description', $brand.' menyediakan rental motor cepat, aman, dan terjangkau. Pilih motor, tentukan durasi, bayar online, lalu ambil di lokasi.') }}
                </p>

                <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="group mt-6 inline-flex items-center gap-3 text-sm">
                    <span class="footer-ikon">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 00-8.5 14.98L2 22l5.17-1.5A9.9 9.9 0 1012.04 2zm5.8 14.06c-.24.68-1.42 1.3-1.96 1.35-.5.05-1.13.07-1.83-.12a16.5 16.5 0 01-1.66-.61c-2.92-1.26-4.82-4.2-4.97-4.4-.14-.2-1.18-1.57-1.18-3s.75-2.13 1.02-2.42c.26-.3.58-.37.77-.37h.55c.18 0 .42-.07.65.5.24.58.82 2 .89 2.15.07.14.12.31.02.5-.1.2-.14.31-.29.48-.14.17-.3.38-.43.5-.14.15-.29.3-.12.6.17.29.74 1.22 1.6 1.98 1.1.98 2.03 1.28 2.32 1.42.29.15.46.12.63-.07.17-.2.72-.84.92-1.13.19-.29.39-.24.65-.14.27.1 1.7.8 1.99.95.29.14.48.22.55.34.07.12.07.7-.17 1.38z" /></svg>
                    </span>
                    <span>
                        <span class="block font-semibold text-[var(--navy-900)] transition group-hover:text-[var(--orange-500)]">Chat Admin</span>
                        @if ($waNumber)
                            <span class="block text-xs">+{{ $waNumber }}</span>
                        @endif
                    </span>
                </a>

                @if (! empty($socials))
                    <div class="mt-6 flex flex-wrap items-center gap-2">
                        @foreach ($socials as $label => $href)
                            <a href="{{ $href }}" target="_blank" rel="noopener" class="footer-sosmed" title="{{ $label }}" aria-label="{{ $label }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $socialIcons[$label] ?? '' !!}</svg>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="lg:w-56">
                <h3 class="text-sm font-bold">Jelajahi</h3>
                <ul class="mt-4 space-y-1 text-sm">
                    @foreach ($navLinks as $link)
                        <li>
                            <a href="{{ $link['href'] }}" class="footer-tautan">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $link['icon'] }}" /></svg>
                                <span>{{ $link['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Baris bawah: cabang, ditengahkan di bawah merek & navigasi --}}
        <div class="mt-12 lg:mt-14">
            @if ($branches->isEmpty())
                <h3 class="text-center text-sm font-bold">Kontak</h3>
                <div class="mx-auto mt-4 max-w-xl">
                    <div class="footer-kartu text-center">
                        <p class="text-xs font-semibold uppercase tracking-wider" style="color: var(--orange-500);">Alamat</p>
                        <p class="mt-1 text-sm leading-relaxed">{{ setting('contact.address', 'Alamat lokasi pengambilan akan diinformasikan oleh admin.') }}</p>
                        <p class="mt-3 text-xs font-semibold uppercase tracking-wider" style="color: var(--orange-500);">Jam operasional</p>
                        <p class="mt-1 text-sm leading-relaxed">{{ setting('contact.hours', 'Setiap hari, 08.00 – 20.00 WIB') }}</p>
                    </div>
                </div>
            @else
                <h3 class="text-center text-sm font-bold">Cabang Kami</h3>
                <x-branch-marquee :branches="$branches" class="mt-5" />
            @endif
        </div>

        {{-- Baris penutup: hak cipta + tautan penting --}}
        <div class="mt-12 flex flex-col-reverse items-start gap-4 border-t border-[var(--border)] pt-6 text-xs sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} {{ $brand }}. Hak cipta dilindungi.</p>

            <nav class="flex flex-wrap items-center gap-x-5 gap-y-2" aria-label="Tautan footer">
                <a href="{{ route('about') }}" class="transition hover:text-[var(--orange-500)]">Tentang Kami</a>
                <a href="{{ route('terms') }}" class="transition hover:text-[var(--orange-500)]">Syarat &amp; Ketentuan</a>
                <a href="{{ url('/kendaraan') }}" class="transition hover:text-[var(--orange-500)]">Pilih Kendaraan</a>
            </nav>
        </div>
    </div>
</footer>
