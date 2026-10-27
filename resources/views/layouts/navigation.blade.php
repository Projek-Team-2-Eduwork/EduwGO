@php
    $waNumber = preg_replace('/\D+/', '', (string) setting('contact.whatsapp', ''));
    // Nomor belum diatur: arahkan ke wa.me tanpa nomor agar tidak menghasilkan tautan rusak.
    $waUrl = $waNumber ? 'https://wa.me/'.$waNumber.'?text='.rawurlencode('Halo, saya butuh bantuan.') : '//wa.me';

    $homeHero = request()->routeIs('home');

    $menu = [
        ['label' => 'Beranda', 'href' => url('/'), 'active' => request()->is('/')],
        ['label' => 'Pilih Kendaraan', 'href' => url('/kendaraan'), 'active' => request()->is('kendaraan*')],
        ['label' => 'Pesanan saya', 'href' => url('/pesanan'), 'active' => request()->is('pesanan*')],
    ];
@endphp

<nav x-data="{
        open: false,
        scrolled: false,
        sync() {
            this.scrolled = window.scrollY > 24;
        }
     }"
     x-init="
        sync();
        window.addEventListener('scroll', () => { scrolled = window.scrollY > 24 }, { passive: true });
        window.addEventListener('pageshow', () => { open = false; sync() });
        requestAnimationFrame(() => document.documentElement.setAttribute('data-nav-ready', ''));
     "
     x-on:resize.window.debounce.100ms="if (window.innerWidth >= 640) { open = false; $dispatch('close-modal', 'mobile-menu'); }"
     x-on:modal-state.window="if ($event.detail.name === 'mobile-menu') { open = $event.detail.show; }"
     :class="{ 'is-scrolled': scrolled }"
     class="navbar-edu sticky top-0 z-40 {{ $homeHero ? 'navbar-edu--hero' : '' }}"
     aria-label="Navigasi utama">
    <div class="navbar-edu-inner">
        <div class="navbar-edu-bar flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-5 lg:gap-8">
                <a href="{{ url('/') }}" class="navbar-brand inline-flex shrink-0 items-center" aria-label="{{ setting('brand.name', 'EduwGo') }}">
                    <x-brand-logo class="block h-9 w-auto" />
                </a>

                <div class="hidden items-center gap-1 sm:flex">
                    @foreach ($menu as $item)
                        <x-nav-link class="hero-nav-link" :href="$item['href']" :active="$item['active']">{{ $item['label'] }}</x-nav-link>
                    @endforeach
                </div>
            </div>

            <div class="hidden items-center gap-2 sm:flex xl:gap-3">
                @unless($homeHero)
                    <div class="hidden lg:block">
                        <x-branch-picker :mobile-sheet="false" class="hero-nav-control max-w-[8rem] xl:max-w-[10rem]" />
                    </div>
                @endunless

                <a href="{{ $waUrl }}" target="_blank" rel="noopener"
                   class="hero-nav-control hidden items-center gap-2 border border-[var(--line)] px-3 py-2 text-sm font-medium text-[var(--ink)] transition hover:bg-[var(--bg)] lg:inline-flex"
                   style="background-color: var(--surface);"
                   title="Butuh bantuan?" aria-label="Butuh bantuan?">
                    <svg class="h-4 w-4 text-[var(--accent)]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 00-8.5 14.98L2 22l5.17-1.5A9.9 9.9 0 1012.04 2zm5.8 14.06c-.24.68-1.42 1.3-1.96 1.35-.5.05-1.13.07-1.83-.12a16.5 16.5 0 01-1.66-.61c-2.92-1.26-4.82-4.2-4.97-4.4-.14-.2-1.18-1.57-1.18-3s.75-2.13 1.02-2.42c.26-.3.58-.37.77-.37h.55c.18 0 .42-.07.65.5.24.58.82 2 .89 2.15.07.14.12.31.02.5-.1.2-.14.31-.29.48-.14.17-.3.38-.43.5-.14.15-.29.3-.12.6.17.29.74 1.22 1.6 1.98 1.1.98 2.03 1.28 2.32 1.42.29.15.46.12.63-.07.17-.2.72-.84.92-1.13.19-.29.39-.24.65-.14.27.1 1.7.8 1.99.95.29.14.48.22.55.34.07.12.07.7-.17 1.38z"/></svg>
                    <span class="hidden xl:inline">Butuh bantuan?</span>
                </a>

                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button type="button" class="flex items-center gap-2 text-sm font-medium text-[var(--ink)] focus:outline-none" aria-label="Menu akun">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold text-[var(--ink)]" style="background-color: var(--accent-soft);">
                                    {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                                </span>
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-[var(--line)] px-4 py-2">
                                <div class="truncate text-sm font-semibold text-[var(--ink)]">{{ Auth::user()->name }}</div>
                                <div class="truncate text-xs text-[var(--ink-muted)]">{{ Auth::user()->email }}</div>
                            </div>

                            <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>

                            @if (Auth::user()->isStaff())
                                <x-dropdown-link :href="route('admin.dashboard')">Dashboard Admin</x-dropdown-link>
                            @endif

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    Keluar
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="btn-primary-edu inline-flex items-center px-5 py-2.5 text-sm">Masuk</a>
                @endauth
            </div>

            <!-- Hamburger (mobile) -->
            <div class="flex items-center gap-2 sm:hidden">
                <button type="button" @click="$dispatch('open-modal', 'mobile-menu')" class="inline-flex items-center justify-center p-2 text-[var(--ink-muted)] transition hover:bg-[var(--bg)] hover:text-[var(--ink)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]" :aria-expanded="open.toString()" aria-controls="mobile-menu" aria-label="Buka menu">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path x-show="! open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="open" style="display: none;" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu mobile -->
    <x-modal name="mobile-menu" placement="navbar" aria-label="Menu navigasi">
        <div id="mobile-menu" class="p-3">
            <div class="flex items-center justify-between px-4 pt-2">
                <h2 class="text-lg text-[var(--ink)]">Menu</h2>
                <button type="button" class="min-h-11 min-w-11 text-2xl" @click="$dispatch('close')" aria-label="Tutup menu">&times;</button>
            </div>
            <div class="space-y-1 pb-3 pt-2">
                @foreach ($menu as $item)
                    <x-responsive-nav-link :href="$item['href']" :active="$item['active']">{{ $item['label'] }}</x-responsive-nav-link>
                @endforeach
            </div>

            <div class="px-4 pb-3">
                @unless($homeHero)
                    <x-branch-picker :mobile-sheet="false" align="left" class="mb-3 w-full justify-between" />
                @endunless

                <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn-secondary-edu inline-flex w-full items-center justify-center px-5 py-2.5 text-sm">
                    Butuh bantuan?
                </a>
            </div>

            <div class="border-t border-[var(--line)] pb-3 pt-4">
                @auth
                    <div class="px-4">
                        <div class="text-base font-medium text-[var(--ink)]">{{ Auth::user()->name }}</div>
                        <div class="text-sm text-[var(--ink-muted)]">{{ Auth::user()->email }}</div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <x-responsive-nav-link :href="route('profile.edit')">Profil</x-responsive-nav-link>

                        @if (Auth::user()->isStaff())
                            <x-responsive-nav-link :href="route('admin.dashboard')">Dashboard Admin</x-responsive-nav-link>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                Keluar
                            </x-responsive-nav-link>
                        </form>
                    </div>
                @else
                    <div class="px-4">
                        <a href="{{ route('login') }}" class="btn-primary-edu inline-flex w-full items-center justify-center px-5 py-2.5 text-sm">Masuk</a>
                    </div>
                @endauth
            </div>
        </div>
    </x-modal>
</nav>
