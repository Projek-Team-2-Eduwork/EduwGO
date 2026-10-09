@php
    $menu = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard'), 'icon' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
        ['label' => 'Daftar Pesanan', 'href' => url('/admin/pesanan'), 'active' => request()->is('admin/pesanan*'), 'icon' => 'M9 5h6M9 9h6m-6 4h6M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z'],
        ['label' => 'Kendaraan', 'href' => url('/admin/kendaraan'), 'active' => request()->is('admin/kendaraan*'), 'icon' => 'M5 17a2 2 0 104 0 2 2 0 00-4 0zm10 0a2 2 0 104 0 2 2 0 00-4 0zM7 17h8M3 17h2m14 0h2M6 9h6l3 5M12 9V6h3'],
        ['label' => 'Tipe Kendaraan', 'href' => url('/admin/tipe-kendaraan'), 'active' => request()->is('admin/tipe-kendaraan*'), 'icon' => 'M7 7h.01M7 3h5a2 2 0 011.4.6l7 7a2 2 0 010 2.8l-5 5a2 2 0 01-2.8 0l-7-7A2 2 0 015 10V5a2 2 0 012-2z'],
        ['label' => 'Pengguna', 'href' => url('/admin/pengguna'), 'active' => request()->is('admin/pengguna*'), 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
        ['label' => 'Pengaturan', 'href' => url('/admin/pengaturan'), 'active' => request()->is('admin/pengaturan*'), 'icon' => 'M10.3 4.3a1 1 0 011.4 0l.7.7a1 1 0 001 .2l1-.3a1 1 0 011.2.7l.3 1a1 1 0 00.7.7l1 .3a1 1 0 01.7 1.2l-.3 1a1 1 0 00.2 1l.7.7a1 1 0 010 1.4l-.7.7a1 1 0 00-.2 1l.3 1a1 1 0 01-.7 1.2l-1 .3a1 1 0 00-.7.7l-.3 1a1 1 0 01-1.2.7l-1-.3a1 1 0 00-1 .2l-.7.7a1 1 0 01-1.4 0l-.7-.7a1 1 0 00-1-.2l-1 .3a1 1 0 01-1.2-.7l-.3-1a1 1 0 00-.7-.7l-1-.3a1 1 0 01-.7-1.2l.3-1a1 1 0 00-.2-1l-.7-.7a1 1 0 010-1.4l.7-.7a1 1 0 00.2-1l-.3-1a1 1 0 01.7-1.2l1-.3a1 1 0 00.7-.7l.3-1a1 1 0 011.2-.7l1 .3a1 1 0 001-.2l.7-.7zM12 9a3 3 0 100 6 3 3 0 000-6z'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head', ['title' => $title ?? 'Admin'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ drawer: false }" @keydown.escape.window="drawer = false" class="min-h-screen lg:flex">
            {{-- Backdrop drawer (mobile) --}}
            <div x-show="drawer" x-transition.opacity @click="drawer = false" style="display: none;" class="fixed inset-0 z-40 bg-[var(--navy-900)] opacity-60 lg:hidden" aria-hidden="true"></div>

            {{-- Sidebar: statis di desktop, drawer di mobile --}}
            <aside
                id="admin-sidebar"
                class="fixed inset-y-0 start-0 z-50 flex w-64 -translate-x-full flex-col border-e border-[var(--border)] transition-transform duration-200 ease-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 rtl:translate-x-full lg:rtl:translate-x-0"
                :class="drawer ? '!translate-x-0' : ''"
                style="background-color: var(--surface);"
                aria-label="Menu admin"
            >
                <div class="flex h-16 shrink-0 items-center justify-between px-5">
                    <a href="{{ route('admin.dashboard') }}" aria-label="{{ setting('brand.name', 'EduwGo') }}">
                        <x-brand-logo class="h-8 w-auto" />
                    </a>

                    <button type="button" @click="drawer = false" class="p-2 text-[var(--ink-muted)] hover:text-[var(--navy-900)] lg:hidden" aria-label="Tutup menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    @foreach ($menu as $item)
                        <a href="{{ $item['href'] }}"
                           @if ($item['active']) aria-current="page" @endif
                           class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium transition {{ $item['active'] ? 'bg-[var(--navy-900)] text-[var(--surface)]' : 'text-[var(--ink-muted)] hover:bg-[var(--bg)] hover:text-[var(--navy-900)]' }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $item['icon'] }}" /></svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="border-t border-[var(--border)] px-3 py-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 px-3 py-2.5 text-sm font-medium text-[var(--ink-muted)] transition hover:bg-[var(--bg)] hover:text-[var(--navy-900)]">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 17l5-5-5-5M20 12H9m4 8H6a2 2 0 01-2-2V6a2 2 0 012-2h7" /></svg>
                            Log Out
                        </button>
                    </form>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                {{-- Navbar atas --}}
                <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-[var(--border)] px-4 sm:px-6" style="background-color: var(--surface);">
                    <div class="flex items-center gap-3">
                        <button type="button" @click="drawer = true" class="p-2 text-[var(--ink-muted)] hover:bg-[var(--bg)] hover:text-[var(--navy-900)] lg:hidden" :aria-expanded="drawer.toString()" aria-controls="admin-sidebar" aria-label="Buka menu">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>

                        @isset($header)
                            <div class="min-w-0 truncate">{{ $header }}</div>
                        @endisset
                    </div>

                    <div class="flex items-center gap-3">
                        <x-theme-toggle />

                        @auth
                            <span class="hidden items-center gap-2 text-sm font-medium text-[var(--navy-900)] sm:inline-flex">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold text-white" style="background-image: var(--accent-gradient);">
                                    {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                                </span>
                                <span class="max-w-[10rem] truncate">{{ Auth::user()->name }}</span>
                            </span>
                        @endauth
                    </div>
                </header>

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>