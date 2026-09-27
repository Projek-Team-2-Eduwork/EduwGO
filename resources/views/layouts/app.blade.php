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
                var preference = @json(auth()->check() ? auth()->user()->preferredTheme() : null);
                var stored = null;

                try {
                    stored = localStorage.getItem('theme');
                } catch (e) {}

                // Prioritas: preferensi user login > localStorage > prefers-color-scheme
                var choice = preference || stored || 'system';
                var systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var dark = choice === 'dark' || (choice === 'system' && systemDark);

                if (dark) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="border-b border-[var(--border)]" style="background-color: var(--surface);">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
