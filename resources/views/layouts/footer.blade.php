@php
    $brand = setting('brand.name', 'EduwGo');
    $waNumber = preg_replace('/\D+/', '', (string) setting('contact.whatsapp', ''));
    $waUrl = 'https://wa.me/'.$waNumber.'?text='.rawurlencode('Halo, saya butuh bantuan.');

    $socials = [
        'Instagram' => setting('social.instagram', '#'),
        'Facebook' => setting('social.facebook', '#'),
        'TikTok' => setting('social.tiktok', '#'),
        'YouTube' => setting('social.youtube', '#'),
    ];
@endphp

<footer class="mt-20 border-t border-[var(--border)]" style="background-color: var(--surface);">
    <div class="mx-auto grid max-w-[1320px] gap-10 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-5 xl:px-0">
        <div class="lg:col-span-2">
            <x-brand-logo class="h-9 w-auto" />
            <p class="mt-4 max-w-sm text-sm leading-relaxed">
                {{ setting('brand.description', $brand.' menyediakan rental motor cepat, aman, dan terjangkau. Pilih motor, tentukan durasi, bayar online, lalu ambil di lokasi.') }}
            </p>
        </div>

        <div>
            <h3 class="text-sm font-bold">About</h3>
            <ul class="mt-4 space-y-2 text-sm">
                <li><a href="{{ route('about') }}" class="hover:text-[var(--orange-500)]">Tentang Kami</a></li>
                <li><a href="{{ url('/#cara-sewa') }}" class="hover:text-[var(--orange-500)]">Cara Sewa</a></li>
                <li><a href="{{ route('terms') }}" class="hover:text-[var(--orange-500)]">Syarat &amp; Ketentuan</a></li>
                <li><a href="{{ url('/kendaraan') }}" class="hover:text-[var(--orange-500)]">Pilih Kendaraan</a></li>
                <li><a href="{{ url('/pesanan') }}" class="hover:text-[var(--orange-500)]">Daftar Pesanan</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-bold">Socials</h3>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($socials as $label => $href)
                    <li><a href="{{ $href }}" target="_blank" rel="noopener" class="hover:text-[var(--orange-500)]">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-bold">Kontak</h3>
            <ul class="mt-4 space-y-2 text-sm">
                @if ($address = setting('contact.address'))
                    <li>{{ $address }}</li>
                @endif
                @if ($hours = setting('contact.hours'))
                    <li>{{ $hours }}</li>
                @endif
                <li>
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn-secondary-edu mt-1 inline-flex items-center px-4 py-2 text-sm">
                        Chat Admin
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="border-t border-[var(--border)] py-4 text-center text-xs">
        &copy; {{ now()->year }} {{ $brand }}. Hak cipta dilindungi.
    </div>
</footer>
