@php($branches = app(App\Services\BranchContext::class)->active())
<footer class="{{ request()->routeIs('home') ? '' : 'mt-16' }} border-t border-[var(--line)]">
    <div class="mx-auto max-w-[1320px] px-4 py-12 sm:px-6">
        <div class="grid gap-10 text-left md:grid-cols-[2fr_1fr_2fr]">
            <div><a href="{{ route('home') }}"><x-brand-logo /></a><p class="mt-4 max-w-xs text-sm leading-6">Sewa motor harian, pilih jadwal lalu ambil di cabang pilihan Anda.</p></div>
            <nav aria-label="Jelajahi"><h3 class="text-sm">Jelajahi</h3><ul class="mt-3 text-sm">
                @foreach(['home' => 'Beranda', 'kendaraan.index' => 'Pilih motor', 'booking.index' => 'Pesanan saya', 'about' => 'Tentang kami', 'terms' => 'Syarat & ketentuan', 'photo-credits' => 'Kredit foto'] as $route => $label)
                    <li><a class="link-underline inline-flex min-h-11 items-center hover:underline" href="{{ route($route) }}">{{ $label }}</a></li>
                @endforeach
            </ul></nav>
            <div><h3 class="text-sm">Cabang</h3><div class="mt-3 space-y-6">
                @forelse($branches as $branch)
                    <div><a href="{{ route('cabang.show', $branch->slug) }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-[var(--ink)]">{{ $branch->name }}</a><p class="text-sm leading-6">{{ $branch->address }}</p><p class="mt-1 text-xs">{{ $branch->operating_hours }}</p>
                        <div class="mt-2 flex flex-wrap gap-x-4">
                            @if($branch->whatsappUrl())<a href="{{ $branch->whatsappUrl() }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center text-sm underline">WhatsApp</a>@endif
                            @foreach($branch->socialLinks() as $label => $url)<a href="{{ $url }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center text-sm underline">{{ $label }}</a>@endforeach
                        </div>
                    </div>
                @empty<p class="text-sm">Informasi cabang akan tersedia di sini.</p>@endforelse
            </div></div>
        </div>
        <p class="mt-10 border-t border-[var(--line)] pt-6 text-xs">&copy; {{ now()->year }} {{ setting('brand.name', 'EduwGo') }}. Hak cipta dilindungi.</p>
    </div>
</footer>
