<x-guest-layout>
    <h1 class="text-3xl sm:text-4xl">Masuk</h1>
    <p class="mt-2 text-sm">Masuk untuk menyewa motor dan memantau pesanan Anda.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Email" class="!text-[var(--navy-900)]" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 !text-red-600 dark:!text-red-400" />
        </div>

        <div>
            <x-input-label for="password" value="Kata sandi" class="!text-[var(--navy-900)]" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 !text-red-600 dark:!text-red-400" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-[var(--border)] shadow-sm accent-[var(--navy-900)]" name="remember">
                <span class="ms-2 text-sm">Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm underline text-[var(--navy-900)] rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]" href="{{ route('password.request') }}">
                    Lupa kata sandi?
                </a>
            @endif
        </div>

        <button type="submit" class="btn-primary-edu w-full px-6 py-3 text-sm">Masuk</button>

        <p class="text-sm text-center">
            Belum punya akun?
            <a class="font-semibold underline text-[var(--navy-900)]" href="{{ route('register') }}">Buat Akun</a>
        </p>
    </form>
</x-guest-layout>
