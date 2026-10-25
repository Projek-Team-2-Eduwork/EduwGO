<section id="syarat-ketentuan" aria-labelledby="judul-syarat-ketentuan" class="landing-section landing-section--surface">
    <div class="landing-inner">
        <x-terms-panel :items="$terms" title="Syarat & Ketentuan Rental Motor" :intro="'Ada '.count($terms).' hal yang perlu Anda ketahui sebelum memesan motor.'">
            <x-slot:actions>
                <a href="{{ route('terms') }}" class="btn-secondary-edu inline-flex items-center gap-2">
                    Baca selengkapnya
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
            </x-slot:actions>
        </x-terms-panel>
    </div>
</section>
