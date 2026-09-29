{{--
    <head> bersama untuk semua layout (user, admin, guest).
    Props: $title (opsional).
--}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ isset($title) && $title ? $title.' — ' : '' }}{{ setting('brand.name', config('app.name', 'EduwGo')) }}</title>

@if ($favicon = setting('brand.favicon'))
    <link rel="icon" href="{{ $favicon }}">
@endif

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
