@props([])

{{--
    Theme-toggle: terang / gelap / ikut sistem.
    Prioritas: users.theme_preference > localStorage > prefers-color-scheme.
    Menyimpan lewat PATCH /profil/tema (hanya untuk user login).
--}}
<div
    x-data="{
        theme: {{ auth()->check() ? "'".auth()->user()->preferredTheme()."'" : "(function () { try { return localStorage.getItem('theme') || 'system'; } catch (e) { return 'system'; } })()" }},
        loggedIn: {{ auth()->check() ? 'true' : 'false' }},
        apply() {
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const dark = this.theme === 'dark' || (this.theme === 'system' && systemDark);

            document.documentElement.classList.toggle('dark', dark);

            try {
                localStorage.setItem('theme', this.theme);
            } catch (e) {}
        },
        async set(theme) {
            this.theme = theme;
            this.apply();

            if (! this.loggedIn) {
                return;
            }

            try {
                await fetch('{{ route('profile.theme') }}', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ theme }),
                });
            } catch (e) {}
        },
    }"
    x-init="$nextTick(() => apply())"
    @theme-changed.window="set($event.detail.theme)"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 border border-[var(--border)] bg-[var(--surface)] p-1']) }}
    role="group"
    aria-label="Pilih tema tampilan"
>
    <button
        type="button"
        class="theme-btn"
        :class="theme === 'light' ? 'is-active' : ''"
        :aria-pressed="theme === 'light'"
        aria-label="Tema terang"
        title="Terang"
        @click="set('light')"
    >
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2M5.6 5.6l1.4 1.4m10 10l1.4 1.4M3 12h2m14 0h2M5.6 18.4L7 17m10-10l1.4-1.4M12 8a4 4 0 100 8 4 4 0 000-8z" />
        </svg>
    </button>

    <button
        type="button"
        class="theme-btn"
        :class="theme === 'dark' ? 'is-active' : ''"
        :aria-pressed="theme === 'dark'"
        aria-label="Tema gelap"
        title="Gelap"
        @click="set('dark')"
    >
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z" />
        </svg>
    </button>

    <button
        type="button"
        class="theme-btn"
        :class="theme === 'system' ? 'is-active' : ''"
        :aria-pressed="theme === 'system'"
        aria-label="Ikuti sistem"
        title="Ikut sistem"
        @click="set('system')"
    >
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 21m5.25-4l.75 4M4 5h16a1 1 0 011 1v9a1 1 0 01-1 1H4a1 1 0 01-1-1V6a1 1 0 011-1z" />
        </svg>
    </button>
</div>
