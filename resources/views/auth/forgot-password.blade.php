<x-guest-layout>
    <h1 class="text-3xl sm:text-4xl">Lupa Kata Sandi</h1>

    <p class="mt-2 mb-4 text-sm">
        Masukkan email Anda dan kami akan mengirim tautan untuk mengatur ulang kata sandi.
    </p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Email" class="!text-[var(--navy-900)]" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 !text-red-600 dark:!text-red-400" />
        </div>

        <button type="submit" class="btn-primary-edu w-full px-6 py-3 text-sm">Kirim Tautan Reset</button>

        <p class="text-sm text-center">
            <a class="font-semibold underline text-[var(--navy-900)]" href="{{ route('login') }}">Kembali ke Masuk</a>
        </p>
    </form>
</x-guest-layout>
