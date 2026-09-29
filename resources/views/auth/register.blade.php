<x-guest-layout>
    <h1 class="text-3xl sm:text-4xl">Buat Akun</h1>
    <p class="mt-2 text-sm">Daftar untuk mulai menyewa motor. Kami akan mengirim email verifikasi.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4" x-data>
        @csrf

        <div>
            <x-input-label for="name" value="Nama lengkap" class="!text-[var(--navy-900)]" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2 !text-red-600 dark:!text-red-400" />
        </div>

        <div>
            <x-input-label for="email" value="Email" class="!text-[var(--navy-900)]" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 !text-red-600 dark:!text-red-400" />
        </div>

        <div>
            <x-input-label for="phone" value="Nomor WhatsApp" class="!text-[var(--navy-900)]" />
            <x-text-input id="phone" class="block mt-1 w-full" type="tel" inputmode="numeric" name="phone" :value="old('phone')" required autocomplete="tel" placeholder="081234567890" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2 !text-red-600 dark:!text-red-400" />
        </div>

        <div>
            <x-input-label for="password" value="Kata sandi" class="!text-[var(--navy-900)]" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 !text-red-600 dark:!text-red-400" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi kata sandi" class="!text-[var(--navy-900)]" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 !text-red-600 dark:!text-red-400" />
        </div>

        <div>
            <label for="terms" class="inline-flex items-start">
                <input id="terms" type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 rounded border-[var(--border)] shadow-sm accent-[var(--navy-900)]">
                <span class="ms-2 text-sm">
                    Saya setuju
                    <button type="button" class="font-semibold underline text-[var(--navy-900)]" x-on:click.prevent="$dispatch('open-modal', 'terms-modal')">Syarat &amp; Ketentuan</button>
                </span>
            </label>
            <x-input-error :messages="$errors->get('terms')" class="mt-2 !text-red-600 dark:!text-red-400" />
        </div>

        <button type="submit" class="btn-primary-edu w-full px-6 py-3 text-sm">Buat Akun</button>

        <p class="text-sm text-center">
            Sudah punya akun?
            <a class="font-semibold underline text-[var(--navy-900)]" href="{{ route('login') }}">Masuk</a>
        </p>
    </form>

    <x-modal name="terms-modal" maxWidth="lg">
        <div class="p-6" style="background-color: var(--surface);">
            <h2 class="text-2xl">Syarat &amp; Ketentuan</h2>
            <div class="mt-4 max-h-[60vh] overflow-y-auto whitespace-pre-line text-sm">{{ $terms }}</div>
            <div class="mt-6 flex justify-end">
                <button type="button" class="btn-primary-edu px-6 py-2 text-sm" x-on:click="$dispatch('close-modal', 'terms-modal')">Tutup</button>
            </div>
        </div>
    </x-modal>
</x-guest-layout>
