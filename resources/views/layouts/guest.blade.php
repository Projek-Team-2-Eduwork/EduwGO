<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>@include('layouts.partials.head', ['title' => $title ?? null])</head>
<body class="font-sans antialiased">
    <div class="grid min-h-screen grid-rows-[auto_1fr] lg:grid-cols-2 lg:grid-rows-1">
        {{-- Panel foto: strip tipis di mobile, setengah layar penuh di desktop. Teks putih di atas gradien gelap (tema apa pun). --}}
        <aside class="auth-panel relative flex h-28 flex-col justify-between overflow-hidden p-5 sm:h-32 lg:h-auto lg:p-12">
            <picture class="absolute inset-0" aria-hidden="true">
                {{-- Desktop panel tegak: pakai foto potret; strip mobile lebar: foto lanskap --}}
                <source media="(min-width: 1024px)" type="image/avif" srcset="{{ asset('images/home/hero-road-mobile.avif') }}">
                <source media="(min-width: 1024px)" srcset="{{ asset('images/home/hero-road-mobile.webp') }}">
                <source type="image/avif" srcset="{{ asset('images/home/hero-road.avif') }}">
                <img src="{{ asset('images/home/hero-road.webp') }}" alt="" width="2400" height="1350" class="h-full w-full object-cover" decoding="async">
            </picture>
            <div class="auth-panel__shade absolute inset-0" aria-hidden="true"></div>
            <a href="{{ route('home') }}" class="relative z-10 w-fit"><x-brand-logo variant="inverse" /></a>
            <div class="relative z-10 hidden max-w-md lg:block">
                <h2 class="text-4xl leading-tight text-[var(--brand-white)]">Perjalanan Anda,<br>mulai dari sini.</h2>
                <p class="mt-5 text-base leading-7 text-[var(--brand-white)] opacity-90">Pilih motor dan jadwal sewa, bayar online, lalu ambil di cabang pilihan Anda.</p>
                <p class="mt-10 text-xs text-[var(--brand-white)] opacity-80">&copy; {{ now()->year }} EduwGo</p>
            </div>
        </aside>
        <div class="flex flex-col bg-[var(--surface)]">
            <nav class="flex min-h-16 items-center lg:min-h-20 justify-between px-6 sm:px-10" aria-label="Navigasi akun">
                <span></span><x-theme-toggle />
            </nav>
            <main class="flex flex-1 items-center justify-center px-6 pb-12 sm:px-10"><div class="anim-masuk w-full max-w-md">{{ $slot }}</div></main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
