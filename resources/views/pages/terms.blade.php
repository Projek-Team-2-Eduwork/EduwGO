<x-app-layout>
    <x-slot:title>Syarat &amp; Ketentuan</x-slot:title>
    @section('meta_description', 'Syarat dan ketentuan penyewaan motor di '.setting('brand.name', 'EduwGo').'.')

    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="font-serif text-4xl sm:text-5xl">Syarat &amp; Ketentuan</h1>
        <p class="mt-3 text-sm sm:text-base">Harap baca ketentuan berikut sebelum menyewa motor.</p>

        {{-- Satu poin satu kartu, senada dengan seksi S&K di beranda --}}
        <ol class="mt-8 grid gap-4 sm:grid-cols-2">
            @foreach ($points as $point)
                <li class="card-surface card-lift flex flex-col gap-3 p-5 sm:p-6">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-[#0A0F1D]" style="background-image: var(--accent-gradient);" aria-hidden="true">{{ $loop->iteration }}</span>
                    <span class="text-sm leading-relaxed sm:text-base">{{ $point }}</span>
                </li>
            @endforeach
        </ol>

        <a href="{{ url('/') }}" class="btn-primary-edu mt-8 inline-flex items-center px-6 py-2.5 text-sm">Kembali ke Home</a>
    </div>
</x-app-layout>
