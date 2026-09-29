<x-app-layout>
    <x-slot:title>Syarat &amp; Ketentuan</x-slot:title>
    @section('meta_description', 'Syarat dan ketentuan penyewaan motor di '.setting('brand.name', 'EduwGo').'.')

    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="font-serif text-4xl sm:text-5xl">Syarat &amp; Ketentuan</h1>
        <p class="mt-3 text-sm sm:text-base">Harap baca ketentuan berikut sebelum menyewa motor.</p>

        <ol class="card-surface mt-8 space-y-4 p-6 sm:p-8">
            @foreach ($points as $point)
                <li class="flex gap-4">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white" style="background-image: var(--accent-gradient);">{{ $loop->iteration }}</span>
                    <span class="pt-1 text-sm leading-relaxed sm:text-base">{{ $point }}</span>
                </li>
            @endforeach
        </ol>

        <a href="{{ url('/') }}" class="btn-primary-edu mt-8 inline-flex items-center px-6 py-2.5 text-sm">Kembali ke Home</a>
    </div>
</x-app-layout>
