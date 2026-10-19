@php
    // Pisahkan nominal harga dari tagline biar bisa diberi warna aksen (mis. "... Mulai Rp75.000/hari").
    preg_match('/^(.*?)(Rp[\d.,]+)(.*)$/u', $tagline, $pisah);
    $judulAwal = $pisah[1] ?? $tagline;
    $judulHarga = $pisah[2] ?? '';
    $judulAkhir = $pisah[3] ?? '';

    // Poin singkat di bawah tombol — diambil dari teks yang sudah ada di halaman ini.
    $poin = ['Bayar online: VA, QRIS, e-wallet, atau retail', 'Unit terawat & siap jalan'];
@endphp

{{-- Hero beranda (kartu): navy token + aksen lime, foto motor melebur di sisi kanan. Font & palet tidak diubah. --}}
<section id="tentang" class="mx-auto max-w-[1320px] px-4 pt-6 sm:px-6 lg:pt-10 xl:px-0">
    <div class="hero-kartu flex min-h-[420px] items-center px-6 pb-40 pt-10 sm:px-10 lg:min-h-[480px] lg:px-14 lg:py-12">
        <div class="hero-kartu__foto">
            <img src="{{ asset('images/home/hero-bali.webp') }}" alt="Motor EduwGo terparkir di tepi pantai Bali" width="1376" height="768" fetchpriority="high" decoding="async">
        </div>

        <div class="relative z-10 max-w-xl">
            <h1 class="text-3xl text-white sm:text-4xl lg:text-[50px] lg:leading-[1.2]">{{ $judulAwal }}<span class="text-[#C6F659]">{{ $judulHarga }}</span>{{ $judulAkhir }}</h1>
            <p class="mt-4 max-w-md text-sm leading-relaxed text-white/70 lg:text-base">
                Pilih motor, tentukan durasi, bayar online, lalu ambil di lokasi. Tanpa ribet, unit terawat dan siap jalan.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a href="{{ route('kendaraan.index') }}" class="hero-kartu__tombol hero-kartu__tombol--utama">
                    Sewa Sekarang
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
                <a href="#cara-sewa" class="hero-kartu__tombol hero-kartu__tombol--sekunder">Cara Kerja</a>
            </div>

            <ul class="hero-kartu__poin mt-6">
                @foreach ($poin as $teks)
                    <li>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg>
                        {{ $teks }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

{{-- Kartu "Cari kendaraan": hanya di mobile (Figma: Mobile - Home) --}}
<section class="relative z-10 mx-auto mt-4 max-w-[1320px] px-4 sm:px-6 lg:hidden xl:px-0" aria-label="Cari kendaraan">
    <form action="{{ route('kendaraan.index') }}" method="GET" class="card-surface space-y-3 p-4 shadow-md">
        <h2 class="text-base">Cari kendaraan</h2>
        <x-form.select name="type" label="Tipe kendaraan" placeholder="Semua tipe" :options="$types" />
        <x-form.datetime name="start_at" label="Tanggal & jam mulai" :min="$minStart->format('Y-m-d\TH:i')" step="3600" :picker="true" />
        <x-form.select name="days" label="Durasi" placeholder="Pilih durasi" :options="collect(range(1, $maxDays))->mapWithKeys(fn ($d) => [$d => $d.' hari'])->all()" />
        <x-primary-button class="w-full">Ayo Cari</x-primary-button>
    </form>
</section>
