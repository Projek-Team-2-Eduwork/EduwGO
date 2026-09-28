<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Styleguide — {{ config('app.name') }}</title>

        <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">

        {{-- Anti-flash: sama seperti layouts.guest --}}
        <script>
            (function () {
                var stored = null;

                try {
                    stored = localStorage.getItem('theme');
                } catch (e) {}

                var systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var dark = stored === 'dark' || ((stored === null || stored === 'system') && systemDark);

                if (dark) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased" style="background-color: var(--bg); background-image: var(--bg-gradient);">
        <div class="max-w-4xl mx-auto px-6 py-10 space-y-10">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-serif" style="color: var(--navy-900);">EduwGo Styleguide</h1>
                <x-theme-toggle />
            </div>

            {{-- Palet warna --}}
            <section>
                <h2 class="text-xl font-serif mb-3" style="color: var(--navy-900);">Palet</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach (['bg', 'surface', 'navy-900', 'navy-700', 'ink-muted', 'orange-500', 'coral-300', 'gold-tan'] as $token)
                        <div class="card-surface p-3">
                            <div class="h-12 rounded mb-2" style="background-color: var(--{{ $token }}); border: 1px solid var(--border);"></div>
                            <p class="text-sm" style="color: var(--ink-muted);">--{{ $token }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Tipografi --}}
            <section>
                <h2 class="text-xl font-serif mb-3" style="color: var(--navy-900);">Tipografi</h2>
                <p class="font-serif text-3xl mb-2" style="color: var(--navy-900);">Heading serif (Instrument Serif)</p>
                <p class="font-sans" style="color: var(--ink-muted);">Body sans (Inter) — teks paragraf biasa dipakai di seluruh halaman.</p>
            </section>

            {{-- Tombol --}}
            <section>
                <h2 class="text-xl font-serif mb-3" style="color: var(--navy-900);">Tombol</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <button class="btn-primary-edu px-5 py-2.5">Tombol Utama</button>
                    <button class="btn-secondary-edu px-4 py-2">Tombol Sekunder</button>
                    <span class="badge-accent">Badge</span>
                </div>
            </section>

            {{-- Card --}}
            <section>
                <h2 class="text-xl font-serif mb-3" style="color: var(--navy-900);">Card</h2>
                <div class="card-surface p-6 max-w-sm">
                    <p class="font-serif text-lg" style="color: var(--navy-900);">Judul card</p>
                    <p class="text-sm mt-1" style="color: var(--ink-muted);">Isi card pakai token surface, border, dan shadow lembut.</p>
                </div>
            </section>
        </div>
    </body>
</html>
