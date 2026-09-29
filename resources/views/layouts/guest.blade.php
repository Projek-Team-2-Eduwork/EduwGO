<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head', ['title' => $title ?? null])
    </head>
    <body class="font-sans antialiased">
        <div class="grid min-h-screen lg:grid-cols-2">
            <!-- Banner kiri (desktop): accent-gradient -->
            <aside class="relative hidden overflow-hidden lg:flex lg:flex-col lg:justify-between lg:p-12" style="background-image: var(--accent-gradient);">
                <a href="{{ url('/') }}" class="inline-flex" aria-label="{{ setting('brand.name', 'EduwGo') }}">
                    <span class="font-serif text-3xl leading-none text-white">{{ setting('brand.name', 'EduwGo') }}</span>
                </a>

                <div>
                    <h2 class="max-w-md font-serif text-5xl leading-tight !text-white">
                        {{ setting('brand.tagline', 'Rental Motor Cepat & Aman') }}
                    </h2>
                    <p class="mt-4 max-w-md text-white/90">
                        Pilih motor, tentukan durasi, bayar online, lalu ambil di lokasi.
                    </p>
                </div>

                <p class="text-sm text-white/80">&copy; {{ now()->year }} {{ setting('brand.name', 'EduwGo') }}</p>
            </aside>

            <!-- Form kanan -->
            <div class="flex flex-col">
                <div class="flex items-center justify-between px-4 py-4 sm:px-8">
                    <a href="{{ url('/') }}" class="lg:invisible" aria-label="{{ setting('brand.name', 'EduwGo') }}">
                        <x-brand-logo class="h-9 w-auto" />
                    </a>

                    <x-theme-toggle />
                </div>

                <div class="flex flex-1 items-center justify-center px-4 pb-10 sm:px-8">
                    <div class="card-surface w-full max-w-md px-6 py-8 sm:px-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
