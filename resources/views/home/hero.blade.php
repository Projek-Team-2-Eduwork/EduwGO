{{-- Hero full-width (Figma: Desktop - Home). Latar accent-gradient dari token; foto bisa diganti nanti. --}}
<section id="tentang" class="relative overflow-hidden" style="background-image: var(--accent-gradient);">
    <div class="mx-auto flex min-h-[380px] max-w-[1320px] items-center px-4 pb-16 pt-12 sm:px-6 lg:min-h-[540px] lg:pb-12 xl:px-0">
        <div class="relative z-10 max-w-xl">
            <h1 class="text-3xl !text-white sm:text-4xl lg:text-[50px] lg:leading-[1.2]">{{ $tagline }}</h1>
            <p class="mt-4 max-w-md text-base leading-relaxed text-white/90 lg:text-lg">
                Pilih motor, tentukan durasi, bayar online, lalu ambil di lokasi. Tanpa ribet, unit terawat dan siap jalan.
            </p>
        </div>

        <svg viewBox="0 0 640 400" class="pointer-events-none absolute -right-8 bottom-0 hidden h-auto w-[46%] max-w-[640px] opacity-30 lg:block" role="img" aria-label="Ilustrasi motor" fill="none" stroke="#fff" stroke-width="10" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="150" cy="290" r="62"/>
            <circle cx="490" cy="290" r="62"/>
            <circle cx="150" cy="290" r="10" fill="#fff"/>
            <circle cx="490" cy="290" r="10" fill="#fff"/>
            <path d="M150 290l70-110h130l70 110M220 180l-20-40h-40M350 180l-15-70h60l25 40M300 290h-80M300 290l50-110"/>
            <path d="M240 150h110c20 0 30 10 30 30" />
        </svg>
    </div>
</section>

{{-- Kartu "Cari kendaraan": hanya di mobile (Figma: Mobile - Home), menimpa bawah hero --}}
<section class="relative z-10 -mt-10 px-4 sm:px-6 lg:hidden" aria-label="Cari kendaraan">
    <form action="{{ route('kendaraan.index') }}" method="GET" class="card-surface space-y-3 p-4 shadow-md">
        <h2 class="text-base">Cari kendaraan</h2>
        <x-form.select name="type" label="Tipe kendaraan" placeholder="Semua tipe" :options="$types" />
        <x-form.datetime name="start_at" label="Tanggal & jam mulai" :min="$minStart->format('Y-m-d\TH:i')" step="3600" />
        <x-form.select name="days" label="Durasi" placeholder="Pilih durasi" :options="collect(range(1, $maxDays))->mapWithKeys(fn ($d) => [$d => $d.' hari'])->all()" />
        <x-primary-button class="w-full">Ayo Cari</x-primary-button>
    </form>
</section>
