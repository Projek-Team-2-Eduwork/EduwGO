<footer class="border-t border-[var(--line)] bg-[var(--bg)]">
    <div class="mx-auto max-w-[1280px] px-4 py-8 sm:px-6">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div><a href="{{ route('home') }}"><x-brand-logo /></a><p class="mt-3 text-sm">Sewa motor harian, ambil di cabang.</p>
                @if(setting('contact.whatsapp'))<a href="https://wa.me/{{ setting('contact.whatsapp') }}" target="_blank" rel="noopener" class="mt-2 inline-flex min-h-11 items-center text-sm font-medium text-[var(--accent)] hover:underline">WhatsApp {{ setting('contact.whatsapp') }}</a>@endif
                @if(setting('contact.hours'))<p class="text-sm">{{ setting('contact.hours') }}</p>@endif</div>
            <nav aria-label="Jelajahi" class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                @foreach(['kendaraan.index' => 'Pilih motor', 'booking.index' => 'Pesanan saya', 'cabang.index' => 'Lokasi cabang', 'terms' => 'Syarat & ketentuan', 'photo-credits' => 'Kredit foto'] as $route => $label)
                    <a class="inline-flex min-h-11 items-center hover:underline" href="{{ route($route) }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div><p class="mb-2 text-sm">Tampilan</p><x-theme-toggle /></div>
        </div>
        <p class="mt-6 text-xs">&copy; {{ now()->year }} {{ setting('brand.name', 'EduwGo') }}. Hak cipta dilindungi.</p>
    </div>
</footer>
