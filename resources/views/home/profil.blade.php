<section id="tentang-penyewaan" class="landing-section landing-section--key">
    <div class="landing-inner">
        <h2 class="reveal text-2xl">Tentang Penyewaan</h2>
        <p class="mt-4 max-w-3xl text-sm leading-7">{{ $profil['deskripsi'] }}</p>
        @if($branches->isEmpty())<p class="mt-4 text-sm">Alamat lokasi pengambilan akan diinformasikan oleh admin.</p>@endif
        <a href="{{ route('about') }}" class="link-underline mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-[var(--ink)] underline underline-offset-4">Selengkapnya tentang penyewaan</a>
    </div>
</section>
