<x-guest-layout>
    <h1 class="text-3xl sm:text-4xl">Verifikasi Email</h1>

    <p class="mt-2 mb-4 text-sm">
        Terima kasih sudah mendaftar! Kami mengirim kode 6 angka ke <strong>{{ auth()->user()->email }}</strong>. Masukkan kode di bawah untuk melanjutkan. Verifikasi diperlukan sebelum Anda bisa checkout. Kode berlaku {{ \App\Services\EmailVerificationCodeService::TTL_MINUTES }} menit.
    </p>

    @if (session('status') == 'verification-code-sent')
        <div class="mb-4 font-medium text-sm text-green-700 dark:text-green-400">
            Kode verifikasi baru sudah dikirim ke email yang Anda daftarkan.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.verify') }}">
        @csrf

        <x-input-label for="code" value="Kode verifikasi" />
        <x-text-input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" autofocus required
            class="mt-1 block w-full text-center text-2xl tracking-[0.5em]" placeholder="••••••" />
        <x-input-error class="mt-2" :messages="$errors->get('code')" />

        <button type="submit" class="btn-primary-edu mt-4 w-full px-6 py-3 text-sm">Verifikasi</button>
    </form>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="text-sm underline text-[var(--navy-900)] rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">Kirim ulang kode</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm underline text-[var(--navy-900)] rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
