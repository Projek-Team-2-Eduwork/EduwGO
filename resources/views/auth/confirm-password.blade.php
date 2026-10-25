<x-guest-layout title="Konfirmasi Kata Sandi">
    <h1 class="text-3xl sm:text-4xl">Konfirmasi Kata Sandi</h1>

    <p class="mt-2 mb-4 text-sm">
        Ini area aman. Konfirmasi kata sandi Anda sebelum melanjutkan.
    </p>

    <form novalidate method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="password" value="Kata sandi" class="!text-[var(--ink)]" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 !text-[var(--danger)] dark:!text-[var(--danger)]" />
        </div>

        <button type="submit" class="btn-primary-edu w-full px-5 py-2.5 text-sm">Konfirmasi</button>
    </form>
</x-guest-layout>
