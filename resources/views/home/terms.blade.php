<section id="syarat-ketentuan" aria-labelledby="judul-syarat-ketentuan" class="mx-auto max-w-[1320px] px-4 pt-12 sm:px-6 lg:pt-20 xl:px-0">
    <x-terms-panel :items="$terms" title="Syarat & Ketentuan Rental Motor" :intro="'Ada '.count($terms).' hal yang perlu Anda ketahui sebelum memesan motor.'">
        <x-slot:actions>
            <a href="{{ route('terms') }}" class="hero-kartu__tombol hero-kartu__tombol--utama focus:outline-none focus-visible:ring-2 focus-visible:ring-[#C6F659] focus-visible:ring-offset-2 focus-visible:ring-offset-[#0a0f1d]">
                Baca selengkapnya
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
        </x-slot:actions>
    </x-terms-panel>
</section>
