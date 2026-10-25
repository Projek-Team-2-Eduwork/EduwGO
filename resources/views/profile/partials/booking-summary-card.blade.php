<section>
    <header>
        <h2 class="text-lg font-medium text-[var(--ink)]">
            Ringkasan pesanan
        </h2>

        <p class="mt-1 text-sm text-[var(--ink-muted)]">
            Jumlah pesanan Anda dan pesanan aktif yang menunggu pembayaran, sudah lunas, atau sedang disewa.
        </p>
    </header>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="section-plain p-4">
            <div class="text-sm">Total pesanan</div>
            <div class="mt-1 text-3xl text-[var(--ink)]">{{ $summary['total'] }}</div>
        </div>

        <div class="section-plain p-4">
            <div class="text-sm">Pesanan aktif</div>
            <div class="mt-1 text-3xl text-[var(--ink)]">{{ $summary['active'] }}</div>
        </div>
    </div>

    <div class="mt-5">
        <a
            href="{{ route('booking.index') }}"
            class="btn-secondary-edu inline-flex items-center px-3 py-1.5 text-sm"
        >
            Lihat pesanan saya
        </a>
    </div>

    @unless ($summary['has_bookings_table'])
        <p class="mt-3 text-xs text-[var(--ink-muted)]">
            Ringkasan pesanan belum tersedia.
        </p>
    @endunless
</section>
