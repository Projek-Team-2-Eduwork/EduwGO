{{-- Isi halaman error: ilustrasi SVG inline + pesan + tombol ke Home. Tanpa query DB. --}}
<section class="mx-auto flex max-w-2xl flex-col items-center px-4 py-16 text-center sm:py-24">
    <x-illustration :name="match ((string) $code) { '403' => '403', '404' => '404', '419' => 'failed', '429' => 'waiting', default => 'service' }" />

    <p class="mt-6 font-serif text-7xl leading-none sm:text-8xl" style="color: var(--accent);">{{ $code }}</p>
    <h1 class="mt-4 font-serif text-3xl sm:text-4xl">{{ $heading }}</h1>
    <p class="mt-3 max-w-md text-sm sm:text-base">{{ $message }}</p>

    <a href="{{ url('/') }}" class="btn-primary-edu mt-8 inline-flex items-center px-5 py-2.5 text-sm">Kembali ke beranda</a>
</section>
