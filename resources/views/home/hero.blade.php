<section id="tentang" class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8 lg:pt-12">
    <div class="grid items-center gap-8 lg:grid-cols-2 lg:gap-12">
        <div class="order-2 lg:order-1">
            <h1 class="font-serif text-4xl leading-tight sm:text-5xl lg:text-6xl">{{ $tagline }}</h1>
            <p class="mt-4 max-w-lg text-base leading-relaxed">
                Pilih motor, tentukan durasi, bayar online, lalu ambil di lokasi. Tanpa ribet, unit terawat dan siap jalan.
            </p>
            <a href="{{ url('/kendaraan') }}" class="btn-primary-edu mt-8 inline-flex items-center px-8 py-3 text-sm uppercase">
                Pilih Kendaraan
            </a>
        </div>

        <div class="order-1 lg:order-2">
            <div class="relative overflow-hidden rounded-lg" style="background-image: var(--accent-gradient);">
                <svg viewBox="0 0 640 400" width="640" height="400" class="block h-auto w-full" role="img" aria-label="Ilustrasi motor" fill="none" stroke="#fff" stroke-width="10" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="150" cy="290" r="62"/>
                    <circle cx="490" cy="290" r="62"/>
                    <circle cx="150" cy="290" r="10" fill="#fff"/>
                    <circle cx="490" cy="290" r="10" fill="#fff"/>
                    <path d="M150 290l70-110h130l70 110M220 180l-20-40h-40M350 180l-15-70h60l25 40M300 290h-80M300 290l50-110"/>
                    <path d="M240 150h110c20 0 30 10 30 30" />
                </svg>
            </div>
        </div>
    </div>
</section>
