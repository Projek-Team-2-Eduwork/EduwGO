<section id="tentang-penyewaan" class="mx-auto max-w-[1320px] px-4 pt-12 sm:px-6 lg:pt-20 xl:px-0">
    <h2 class="text-2xl">Tentang Penyewaan</h2>
    <p class="mt-2 text-sm">Kenali penyedia layanan sebelum menyewa motor.</p>

    <div class="card-surface mt-6 p-5 sm:p-6">
        <div class="flex items-start gap-4">
            @if ($profil['logo'])
                <img src="{{ asset($profil['logo']) }}" alt="Logo {{ $profil['nama'] }}" class="h-14 w-auto rounded-lg object-contain" loading="lazy">
            @else
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-lg text-white" style="background-color: var(--orange-500);" aria-hidden="true">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </span>
            @endif
            <div>
                <h3 class="text-sm">{{ $profil['nama'] }}</h3>
                <p class="mt-1 text-xs leading-relaxed">{{ $profil['deskripsi'] }}</p>
            </div>
        </div>

        @if ($branches->isEmpty())
            <p class="mt-6 text-xs leading-relaxed">Alamat lokasi pengambilan akan diinformasikan oleh admin.</p>
        @else
            <h4 class="mt-6 text-sm font-semibold">Cabang Kami</h4>
            <x-branch-marquee :branches="$branches" class="mt-3" />
        @endif

        <a href="{{ route('about') }}" class="btn-primary-edu mt-6 inline-flex items-center px-5 py-2.5 text-xs">Selengkapnya</a>
    </div>
</section>
