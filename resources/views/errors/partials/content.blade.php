{{-- Isi halaman error: ilustrasi SVG inline + pesan + tombol ke Home. Tanpa query DB. --}}
<section class="mx-auto flex max-w-2xl flex-col items-center px-4 py-16 text-center sm:py-24">
    <svg class="h-40 w-auto sm:h-48" viewBox="0 0 240 160" fill="none" role="img" aria-label="Ilustrasi motor" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="120" cy="140" rx="100" ry="8" fill="var(--border)" opacity=".6"/>
        <path d="M20 140h40M180 140h44" stroke="var(--border)" stroke-width="3" stroke-linecap="round" stroke-dasharray="2 10"/>
        <circle cx="66" cy="112" r="22" stroke="var(--navy-900)" stroke-width="5"/>
        <circle cx="66" cy="112" r="6" fill="var(--orange-500)"/>
        <circle cx="176" cy="112" r="22" stroke="var(--navy-900)" stroke-width="5"/>
        <circle cx="176" cy="112" r="6" fill="var(--orange-500)"/>
        <path d="M66 112l30-38h44l36 38" stroke="var(--navy-900)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M96 74l-8-16h-14M140 74l14-24h20" stroke="var(--navy-900)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M100 74h44l-6 16h-32z" fill="var(--orange-500)"/>
    </svg>

    <p class="mt-6 font-serif text-7xl leading-none sm:text-8xl" style="color: var(--orange-500);">{{ $code }}</p>
    <h1 class="mt-4 font-serif text-3xl sm:text-4xl">{{ $heading }}</h1>
    <p class="mt-3 max-w-md text-sm sm:text-base">{{ $message }}</p>

    <a href="{{ url('/') }}" class="btn-primary-edu mt-8 inline-flex items-center px-6 py-2.5 text-sm">Kembali ke Home</a>
</section>
