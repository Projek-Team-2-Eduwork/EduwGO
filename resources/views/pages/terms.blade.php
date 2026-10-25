<x-app-layout>
    <x-slot:title>Syarat &amp; Ketentuan</x-slot:title>
    @section('meta_description', 'Syarat dan ketentuan penyewaan motor di '.setting('brand.name', 'EduwGo').'.')

    {{-- Bentuk sama dengan seksi S&K di beranda; di sini judul jadi h1 dan aksi mengarah ke langkah berikutnya --}}
    <section aria-labelledby="judul-syarat-ketentuan" class="mx-auto max-w-[1320px] px-4 pt-6 sm:px-6 lg:pt-10 xl:px-0">
        <x-terms-panel heading="h1" :items="$points" title="Syarat & Ketentuan" intro="Harap baca ketentuan berikut sebelum menyewa motor.">
            <x-slot:actions>
                <a href="{{ route('kendaraan.index') }}" class="hero-kartu__tombol hero-kartu__tombol--utama focus:outline-none focus-visible:ring-2 focus-visible:ring-[#C6F659] focus-visible:ring-offset-2 focus-visible:ring-offset-[#0a0f1d]">
                    Lihat Motor
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
                <a href="{{ url('/') }}" class="hero-kartu__tombol hero-kartu__tombol--sekunder focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60">Kembali ke Home</a>
            </x-slot:actions>
        </x-terms-panel>
    </section>
</x-app-layout>
