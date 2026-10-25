<x-guest-layout title="Atur Ulang Kata Sandi">
    <h1 class="text-3xl sm:text-4xl">Atur Ulang Kata Sandi</h1>

    <form novalidate method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" value="Email" class="!text-[var(--ink)]" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 !text-[var(--danger)] dark:!text-[var(--danger)]" />
        </div>

        <div>
            <x-input-label for="password" value="Kata sandi baru" class="!text-[var(--ink)]" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 !text-[var(--danger)] dark:!text-[var(--danger)]" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi kata sandi" class="!text-[var(--ink)]" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 !text-[var(--danger)] dark:!text-[var(--danger)]" />
        </div>

        <button type="submit" class="btn-primary-edu w-full px-5 py-2.5 text-sm">Atur Ulang Kata Sandi</button>
    </form>
</x-guest-layout>
