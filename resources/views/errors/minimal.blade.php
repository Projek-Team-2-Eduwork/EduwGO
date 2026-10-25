{{--
    Layout mandiri untuk 429/500/503 dan fallback error lain: TIDAK memakai query DB, session, atau auth,
    supaya tetap tampil saat database/session bermasalah.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('components.site-icons')
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $heading ?? trim($__env->yieldContent('title')) }} — {{ config('app.name', 'EduwGo') }}">
        <meta name="twitter:title" content="{{ $heading ?? trim($__env->yieldContent('title')) }} — {{ config('app.name', 'EduwGo') }}">
        <meta name="twitter:image" content="{{ asset('images/brand/og.png') }}">
        <meta property="og:image" content="{{ asset('images/brand/og.png') }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="robots" content="noindex">
        <title>@yield('title') — {{ config('app.name', 'EduwGo') }}</title>
        <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
        <script>
            (function () {
                var stored = null;

                try {
                    stored = localStorage.getItem('theme');
                } catch (e) {}

                var choice = stored || 'system';
                if (choice === 'dark' || (choice === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen flex-col">
            <header class="border-b border-[var(--line)]" style="background-color: var(--surface);">
                <div class="mx-auto flex h-16 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                    <a href="{{ url('/') }}" class="font-serif text-2xl leading-none" style="color: var(--ink);"><x-brand-logo :configured="false" /></a>
                </div>
            </header>

            <main class="flex-1 anim-masuk">
                {{-- View error bawaan Laravel (401, 402, 429, ...) meng-extend layout ini lewat @section, tanpa variabel. --}}
                @include('errors.partials.content', [
                    'code' => $code ?? trim($__env->yieldContent('code')),
                    'heading' => $heading ?? trim($__env->yieldContent('title')),
                    'message' => $message ?? trim($__env->yieldContent('message')),
                ])
            </main>

            <footer class="border-t border-[var(--line)] py-4 text-center text-xs" style="background-color: var(--surface);">
                &copy; {{ now()->year }} {{ config('app.name', 'EduwGo') }}. Hak cipta dilindungi.
            </footer>
        </div>
    </body>
</html>
