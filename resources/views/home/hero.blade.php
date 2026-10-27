<section id="beranda" class="home-hero">
    <div class="home-hero-top">
        <div class="home-hero-visual"><x-picture src="images/home/hero-road.webp" alt="Jalan kota dengan pengendara motor dan gedung tinggi pada siang hari" width="1920" height="755" class="home-hero-photo" fetchpriority="high" decoding="async" /></div>
        <div class="home-hero-scrim" aria-hidden="true"></div>
        <div class="home-hero-copy anim-masuk">
            <h1>Sewa motor harian</h1>
            <p class="home-hero-subtitle">
                @if($minimumPrice !== null)Mulai {{ rupiah($minimumPrice) }}/hari · @endif
                @if($branches->isEmpty())Informasi cabang segera tersedia
                @elseif($branches->count() <= 3)ambil di {{ $branches->map(fn ($branch) => preg_replace('/^Cabang\s+/iu', '', $branch->name))->join(', ', ' atau ') }}
                @else ambil di {{ $branches->count() }} cabang @endif
            </p>
        </div>
    </div>
    <div class="landing-inner">
        <form id="hero-search" method="GET" action="{{ route('kendaraan.index') }}" class="hero-search">
            <x-form.select name="cabang" label="Cabang" :options="$branches->pluck('name', 'slug')" :value="$currentBranch?->slug" :placeholder="$branches->isEmpty() ? 'Belum ada cabang aktif' : null" :disabled="$branches->isEmpty()" required />
            <x-form.datetime name="start_at" label="Tanggal & jam mulai" :value="$minStart->format('Y-m-d\TH:i')" :min="$minStart->format('Y-m-d\TH:i')" step="3600" :picker="true" required />
            <x-form.select name="days" label="Durasi" :options="collect(range(1, $maxDays))->mapWithKeys(fn ($day) => [$day => $day.' hari'])" :value="1" required />
            <x-primary-button :disabled="$branches->isEmpty()" class="w-full">Cari motor</x-primary-button>
        </form>
    </div>
</section>
