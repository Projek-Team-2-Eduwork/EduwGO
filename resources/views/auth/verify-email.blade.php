<x-guest-layout>
    <h1 class="text-3xl sm:text-4xl">Verifikasi Email</h1>

    <p class="mt-2 mb-4 text-sm">
        Terima kasih sudah mendaftar! Sebelum melanjutkan, klik tautan verifikasi yang baru kami kirim ke email Anda. Verifikasi diperlukan sebelum Anda bisa checkout. Belum menerima email? Kami bisa mengirim ulang.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-700 dark:text-green-400">
            Tautan verifikasi baru sudah dikirim ke email yang Anda daftarkan.
        </div>
    @endif

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary-edu px-6 py-3 text-sm">Kirim Ulang Email Verifikasi</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm underline text-[var(--navy-900)] rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--orange-500)]">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
