<section id="beranda" class="hero-road anim-masuk relative w-full lg:mb-24">
    <div class="hero-road__visual relative flex items-center overflow-hidden">
        <picture class="absolute inset-0">
            <source media="(max-width: 1023px)" type="image/avif" srcset="{{ asset('images/home/hero-road-mobile.avif') }}">
            <source media="(max-width: 1023px)" srcset="{{ asset('images/home/hero-road-mobile.webp') }}">
            <source type="image/avif" srcset="{{ asset('images/home/hero-road.avif') }}">
            <img src="{{ asset('images/home/hero-road.webp') }}" alt="Jalan aspal terbuka berkelok di antara pepohonan dalam cahaya pagi" width="2400" height="1350" class="hero-road__image h-full w-full object-cover" fetchpriority="high" decoding="async">
        </picture>
        <div class="hero-road__shade absolute inset-0" aria-hidden="true"></div>
        <div class="anim-masuk relative w-full px-5 pb-10 pt-24 sm:px-8 lg:px-[5vw] lg:pb-24">
            <h1 class="hero-road__title max-w-[20ch] font-medium">Sewa motor harian,<br>ambil di cabang</h1>
            @php $tagline = setting('brand.tagline'); @endphp
            @if($tagline)
                <p class="hero-road__subtitle mt-4 text-lg leading-7">{{ $tagline }}</p>
            @else
                <p class="hero-road__subtitle mt-4 text-lg leading-7">Pilih jadwal Anda. {{ $branchAvailability }}.</p>
            @endif
        </div>
    </div>

    <form id="hero-search" method="GET" action="{{ route('kendaraan.index') }}" class="hero-search grid w-full items-end gap-4 border-y border-[var(--line)] bg-[var(--surface)] p-5 sm:p-6 lg:absolute lg:bottom-0 lg:left-0 lg:grid-cols-[1.2fr_1.5fr_.8fr_auto] lg:px-[5vw]">
        <x-form.select name="cabang" label="Cabang" :options="$branches->pluck('name', 'slug')" :value="$currentBranch?->slug" :placeholder="$branches->isEmpty() ? 'Belum ada cabang aktif' : null" :disabled="$branches->isEmpty()" required />
        <x-form.datetime name="start_at" label="Tanggal & jam mulai" :value="$minStart->format('Y-m-d\TH:i')" :min="$minStart->format('Y-m-d\TH:i')" step="3600" :picker="true" required />
        <x-form.select name="days" label="Durasi" :options="collect(range(1, $maxDays))->mapWithKeys(fn ($day) => [$day => $day.' hari'])" :value="1" required />
        <x-primary-button :disabled="$branches->isEmpty()" class="w-full">Cari motor</x-primary-button>
        @if($minimumPrice !== null)<p class="text-xs lg:col-span-4">Mulai {{ rupiah($minimumPrice) }}/hari. Harga mengikuti cabang dan motor pilihan Anda.</p>@endif
    </form>
</section>
