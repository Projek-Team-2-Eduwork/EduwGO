<section>
    <header>
        <h2 class="text-lg font-medium text-[var(--navy-900)]">
            Ringkasan Booking
        </h2>

        <p class="mt-1 text-sm text-[var(--ink-muted)]">
            Jumlah booking kamu dan booking yang masih aktif (pending, lunas, sedang disewa).
        </p>
    </header>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="border border-[var(--border)] p-4" style="background-color: var(--surface);">
            <div class="text-sm text-[var(--ink-muted)]">Total booking</div>
            <div class="mt-1 text-3xl text-[var(--navy-900)]">{{ $summary['total'] }}</div>
        </div>

        <div class="border border-[var(--border)] p-4" style="background-color: var(--surface);">
            <div class="text-sm text-[var(--ink-muted)]">Booking aktif</div>
            <div class="mt-1 text-3xl text-[var(--navy-900)]">{{ $summary['active'] }}</div>
        </div>
    </div>

    <div class="mt-5">
        <a
            href="{{ \Illuminate\Support\Facades\Route::has('pesanan.index') ? route('pesanan.index') : route('dashboard') }}"
            class="btn-secondary-edu inline-flex items-center px-4 py-2 text-xs uppercase"
        >
            Lihat Daftar Pesanan
        </a>
    </div>

    @unless ($summary['has_bookings_table'])
        <p class="mt-3 text-xs text-[var(--ink-muted)]">
            Data booking akan tampil otomatis setelah tabel booking tersedia.
        </p>
    @endunless
</section>
