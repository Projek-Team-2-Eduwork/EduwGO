{{--
    Layout mandiri untuk 500/503: TIDAK memakai query DB, session, atau auth,
    supaya tetap tampil saat database/session bermasalah.
--}}
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
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
            <header class="border-b border-[var(--border)]" style="background-color: var(--surface);">
                <div class="mx-auto flex h-16 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                    <a href="{{ url('/') }}" class="font-serif text-2xl leading-none" style="color: var(--navy-900);">{{ config('app.name', 'EduwGo') }}</a>
                </div>
            </header>

            <main class="flex-1">
                @include('errors.partials.content', ['code' => $code, 'heading' => $heading, 'message' => $message])
            </main>

            <footer class="border-t border-[var(--border)] py-4 text-center text-xs" style="background-color: var(--surface);">
                &copy; {{ now()->year }} {{ config('app.name', 'EduwGo') }}. Hak cipta dilindungi.
            </footer>
        </div>
    </body>
</html>
