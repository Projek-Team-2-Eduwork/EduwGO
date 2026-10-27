@use('Database\Seeders\RoleSeeder')
@php
    $menu = [
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard'), 'icon' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
        ['label' => 'Pesanan', 'badge' => $badges['pesanan'] ?? 0, 'href' => url('/admin/pesanan'), 'active' => request()->is('admin/pesanan*'), 'icon' => 'M9 5h6M9 9h6m-6 4h6M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z'],
        ['label' => 'Kendaraan', 'href' => url('/admin/kendaraan'), 'active' => request()->is('admin/kendaraan*'), 'icon' => 'M5 17a2 2 0 104 0 2 2 0 00-4 0zm10 0a2 2 0 104 0 2 2 0 00-4 0zM7 17h8M3 17h2m14 0h2M6 9h6l3 5M12 9V6h3'],
        ['label' => 'Jadwal armada', 'href' => route('admin.jadwal-armada'), 'active' => request()->routeIs('admin.jadwal-armada'), 'icon' => 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm3 10h3'],
        ['label' => 'Perlu refund', 'badge' => $badges['refund'] ?? 0, 'href' => route('admin.refund.index'), 'active' => request()->routeIs('admin.refund.*'), 'icon' => 'M3 10h11a5 5 0 010 10h-2M3 10l4-4M3 10l4 4'],
        ['label' => 'Tipe Kendaraan', 'only' => RoleSeeder::SUPERADMIN, 'href' => url('/admin/tipe-kendaraan'), 'active' => request()->is('admin/tipe-kendaraan*'), 'icon' => 'M7 7h.01M7 3h5a2 2 0 011.4.6l7 7a2 2 0 010 2.8l-5 5a2 2 0 01-2.8 0l-7-7A2 2 0 015 10V5a2 2 0 012-2z'],
        ['label' => 'Cabang', 'only' => RoleSeeder::SUPERADMIN, 'href' => url('/admin/cabang'), 'active' => request()->is('admin/cabang*'), 'icon' => 'M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10zm0-8a2 2 0 100-4 2 2 0 000 4z'],
        ['label' => 'Pengguna', 'only' => RoleSeeder::SUPERADMIN, 'href' => url('/admin/pengguna'), 'active' => request()->is('admin/pengguna*'), 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
        ['label' => 'Pengaturan', 'href' => auth()->user()->isSuperadmin() ? url('/admin/pengaturan') : url('/admin/pengaturan-cabang'), 'active' => request()->is('admin/pengaturan*'), 'icon' => 'M10.3 4.3a1 1 0 011.4 0l.7.7a1 1 0 001 .2l1-.3a1 1 0 011.2.7l.3 1a1 1 0 00.7.7l1 .3a1 1 0 01.7 1.2l-.3 1a1 1 0 00.2 1l.7.7a1 1 0 010 1.4l-.7.7a1 1 0 00-.2 1l.3 1a1 1 0 01-.7 1.2l-1 .3a1 1 0 00-.7.7l-.3 1a1 1 0 01-1.2.7l-1-.3a1 1 0 00-1 .2l-.7.7a1 1 0 01-1.4 0l-.7-.7a1 1 0 00-1-.2l-1 .3a1 1 0 01-1.2-.7l-.3-1a1 1 0 00-.7-.7l-1-.3a1 1 0 01-.7-1.2l.3-1a1 1 0 00-.2-1l-.7-.7a1 1 0 010-1.4l.7-.7a1 1 0 00.2-1l-.3-1a1 1 0 01.7-1.2l1-.3a1 1 0 00.7-.7l.3-1a1 1 0 011.2-.7l1 .3a1 1 0 001-.2l.7-.7zM12 9a3 3 0 100 6 3 3 0 000-6z'],
    ];

    // Menu bertanda 'only' => 'superadmin' disembunyikan dari admin cabang
    $menu = array_values(array_filter($menu, fn ($item) => ! isset($item['only']) || auth()->user()->isSuperadmin()));
    $adminBranch = auth()->user()->isSuperadmin() ? 'Semua cabang' : (auth()->user()->branch?->name ?? 'Belum ada cabang');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head', ['title' => $title ?? 'Admin'])

        {{-- Anti-flash: pasang mode "rail" sidebar sebelum render (khusus desktop) --}}
        <script>
            (function () {
                try {
                    if (localStorage.getItem('admin-rail') === '1') {
                        document.documentElement.setAttribute('data-admin-rail', '');
                    }
                } catch (e) {}
            })();
        </script>
    </head>
    <body class="font-sans antialiased">
        <div
            x-data="{
                drawer: false,
                mobile: window.matchMedia('(max-width: 1023px)').matches,
                rail: document.documentElement.hasAttribute('data-admin-rail'),
                init() {
                    const media = window.matchMedia('(max-width: 1023px)');
                    this.mobile = media.matches;
                    media.addEventListener('change', event => {
                        this.mobile = event.matches;
                        if (! event.matches) this.drawer = false;
                    });
                },
                toggleRail() {
                    this.rail = ! this.rail;
                    document.documentElement.toggleAttribute('data-admin-rail', this.rail);
                    try { localStorage.setItem('admin-rail', this.rail ? '1' : '0'); } catch (e) {}
                },
            }"
            @keydown.escape.window="if (drawer) { drawer = false; $nextTick(() => $refs.adminMenuButton?.focus()) }"
            x-effect="document.body.classList.toggle('admin-drawer-open', drawer)"
            class="min-h-screen lg:flex"
        >
            {{-- Backdrop drawer (mobile) --}}
            <div x-show="drawer" x-transition.opacity @click="drawer = false; $nextTick(() => $refs.adminMenuButton?.focus())" style="display: none;" class="fixed inset-0 z-40 scrim lg:hidden" aria-hidden="true"></div>

            {{-- Sidebar: rail di desktop, drawer di mobile --}}
            <aside
                id="admin-sidebar"
                x-ref="adminSidebar"
                x-on:keydown.tab="if (drawer) window.trapPanelFocus($event, $el)"
                :inert="mobile && ! drawer"
                class="fixed inset-y-0 start-0 z-50 flex w-64 -translate-x-full flex-col border-e border-[var(--line)] transition-[width,transform] duration-200 ease-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 rtl:translate-x-full lg:rtl:translate-x-0"
                :class="drawer ? '!translate-x-0' : ''"
                style="background-color: var(--surface);"
                aria-label="Menu admin"
            >
                <div class="admin-sidebar-head flex h-16 shrink-0 items-center justify-between px-5">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5" aria-label="{{ setting('brand.name', 'EduwGo') }}">
                        {{-- Monogram: hanya tampil saat sidebar mode rail --}}
                        <span class="admin-brand-rail h-8 w-8 shrink-0 items-center justify-center" aria-hidden="true"><x-brand-logo variant="mark" class="h-8 w-8" /></span>
                        <x-brand-logo class="admin-brand-text h-8 w-auto" />
                    </a>

                    <button type="button" x-ref="adminDrawerClose" @click="drawer = false; $nextTick(() => $refs.adminMenuButton?.focus())" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg p-2 text-[var(--ink-muted)] transition hover:bg-[var(--bg)] hover:text-[var(--ink)] lg:hidden" aria-label="Tutup menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto overscroll-contain px-3 py-4">
                    @foreach ($menu as $item)
                        <a href="{{ $item['href'] }}"
                           @if ($item['active']) aria-current="page" @endif
                           data-label="{{ $item['label'] }}"
                           :title="rail ? $el.dataset.label : null"
                           class="admin-nav-link relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ $item['active'] ? 'is-active font-semibold text-[var(--ink)]' : 'font-medium text-[var(--ink-muted)] hover:bg-[var(--bg)] hover:text-[var(--ink)]' }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $item['icon'] }}" /></svg>
                            <span class="admin-nav-label truncate">{{ $item['label'] }}</span>
                            @if (! empty($item['badge']))
                                <span class="admin-nav-badge ms-auto inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-xs font-bold" aria-label="{{ $item['badge'] }} perlu tindakan">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>

                <div class="border-t border-[var(--line)] px-3 py-4">
                    @auth
                        <div class="mb-3 flex items-center gap-3 rounded-lg border border-[var(--line)] px-3 py-2.5 sm:hidden">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-[var(--ink)]" style="background-color: var(--accent-soft);" aria-hidden="true">
                                {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-[var(--ink)]">{{ Auth::user()->name }}</p>
                                <p class="truncate text-xs">{{ auth()->user()->isSuperadmin() ? 'Superadmin' : 'Admin' }} &middot; {{ $adminBranch }}</p>
                            </div>
                        </div>
                    @endauth

                    <div class="mb-3 flex items-center justify-between gap-2 px-3 sm:hidden">
                        <span class="text-sm font-medium">Tema</span>
                        <x-theme-toggle />
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" data-label="Keluar" :title="rail ? $el.dataset.label : null" class="admin-logout flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-[var(--ink-muted)] transition hover:bg-[var(--bg)] hover:text-[var(--ink)]">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 17l5-5-5-5M20 12H9m4 8H6a2 2 0 01-2-2V6a2 2 0 012-2h7" /></svg>
                            <span class="admin-nav-label truncate">Keluar</span>
                        </button>
                    </form>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                {{-- Navbar atas --}}
                <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-[var(--line)] px-4 sm:px-6" style="background-color: var(--surface);">
                    <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                        {{-- Tombol buka/tutup sidebar (desktop) --}}
                        <button type="button" @click="toggleRail()"
                                :aria-expanded="(! rail).toString()"
                                aria-controls="admin-sidebar"
                                aria-label="Buka atau tutup menu samping"
                                class="hidden shrink-0 min-h-11 min-w-11 rounded-lg p-2 text-[var(--ink-muted)] transition hover:bg-[var(--bg)] hover:text-[var(--ink)] lg:inline-flex">
                            <svg x-show="! rail" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="16" rx="2" />
                                <path d="M9 4v16M15 9.5 12.5 12l2.5 2.5" />
                            </svg>
                            <svg x-show="rail" style="display: none;" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="16" rx="2" />
                                <path d="M9 4v16M12.5 9.5 15 12l-2.5 2.5" />
                            </svg>
                        </button>

                        {{-- Tombol drawer (mobile) --}}
                        <button type="button" x-ref="adminMenuButton" @click="drawer = true; $nextTick(() => $refs.adminDrawerClose?.focus())" class="min-h-11 min-w-11 rounded-lg p-2 text-[var(--ink-muted)] transition hover:bg-[var(--bg)] hover:text-[var(--ink)] lg:hidden" :aria-expanded="drawer.toString()" aria-controls="admin-sidebar" aria-label="Buka menu">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>

                        @isset($header)
                            <div class="min-w-0 flex-1 truncate">{{ $header }}</div>
                        @endisset
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        {{-- Mobile: tombol tema pindah ke drawer supaya judul halaman tidak terdesak --}}
                        <x-theme-toggle class="hidden sm:inline-flex" />

                        @auth
                            <span class="hidden max-w-[12rem] truncate rounded-full border border-[var(--line)] px-3 py-1 text-xs font-semibold text-[var(--ink)] md:inline-block" title="Cakupan data admin">{{ auth()->user()->isSuperadmin() ? 'Superadmin' : 'Admin' }} &middot; {{ $adminBranch }}</span>
                            <span class="hidden items-center gap-2 text-sm font-medium text-[var(--ink)] sm:inline-flex">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold text-[var(--ink)]" style="background-color: var(--accent-soft);">
                                    {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                                </span>
                                <span class="max-w-[10rem] truncate">{{ Auth::user()->name }}</span>
                            </span>
                        @endauth
                    </div>
                </header>

                <main class="admin-content flex-1 px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
