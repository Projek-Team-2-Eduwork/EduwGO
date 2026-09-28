<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts (self-host agar tetap jalan offline) -->
        <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">

        <!-- Anti-flash: set class dark sebelum render (SPEC bagian 8) -->
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

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-[var(--ink-muted)]" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 card-surface overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
