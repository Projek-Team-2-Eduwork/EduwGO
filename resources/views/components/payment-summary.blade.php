@props(['booking', 'payment' => null])

@php
    use App\Services\XenditService;

    $statusPembayaran = match ($payment?->status) {
        'paid' => ['label' => 'Lunas', 'class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'],
        'pending' => ['label' => 'Menunggu Pembayaran', 'class' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200'],
        'expired' => ['label' => 'Kedaluwarsa', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'],
        'failed' => ['label' => 'Gagal', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'],
        default => ['label' => 'Belum Ada Transaksi', 'class' => 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-100'],
    };

    $labelMetode = $payment
        ? XenditService::paymentLabel($payment->payment_channel, $payment->method)
        : 'Belum ada metode terpilih';
@endphp

<section aria-label="Ringkasan pembayaran">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-lg font-bold">Ringkasan Pembayaran</h3>
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusPembayaran['class'] }}">
            {{ $statusPembayaran['label'] }}
        </span>
    </div>

    @if ($payment)
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
            {{-- Kolom detail transaksi --}}
            <dl class="space-y-4 text-sm lg:col-span-3">
                <div class="flex flex-wrap items-start justify-between gap-2 border-b border-[var(--border)] pb-3">
                    <dt class="text-[var(--ink-muted)]">Metode Pembayaran</dt>
                    <dd class="flex items-center gap-2 font-semibold text-[var(--navy-900)]">
                        @forelse (XenditService::channelLogoUrls($payment->payment_channel) as $logo)
                            <span class="flex h-6 w-14 items-center justify-center rounded border border-[var(--border)] bg-white px-1.5 py-0.5">
                                <img src="{{ $logo }}" alt="" class="h-full object-contain" loading="lazy">
                            </span>
                        @empty
                            <span class="flex h-6 w-6 items-center justify-center rounded bg-[var(--bg)] text-[var(--ink-muted)]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </span>
                        @endforelse
                        {{ $labelMetode }}
                    </dd>
                </div>

                <div class="flex flex-wrap items-start justify-between gap-2 border-b border-[var(--border)] pb-3">
                    <dt class="text-[var(--ink-muted)]">ID Transaksi</dt>
                    <dd class="break-all font-mono text-xs font-semibold text-[var(--navy-900)]">{{ $payment->gateway_reference ?? '-' }}</dd>
                </div>

                <div class="flex flex-wrap items-start justify-between gap-2 border-b border-[var(--border)] pb-3">
                    <dt class="text-[var(--ink-muted)]">Waktu Pembayaran</dt>
                    <dd class="font-semibold text-[var(--navy-900)]">
                        {{ $payment->paid_at ? $payment->paid_at->translatedFormat('D, d M Y H:i') : 'Belum dibayar' }}
                    </dd>
                </div>

                <div class="flex flex-wrap items-start justify-between gap-2">
                    <dt class="text-[var(--ink-muted)]">Batas Pembayaran</dt>
                    <dd class="font-semibold text-[var(--navy-900)]">
                        {{ $payment->expires_at ? $payment->expires_at->translatedFormat('D, d M Y H:i') : '-' }}
                    </dd>
                </div>
            </dl>

            {{-- Kolom nominal (gaya invoice) --}}
            <div class="rounded-xl border border-[var(--border)] bg-[var(--bg)] p-4 lg:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-[var(--ink-muted)]">Jumlah Tagihan</p>
                <p class="mt-1 text-2xl font-black text-[var(--navy-900)]">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</p>

                <div class="mt-3 space-y-1 border-t border-[var(--border)] pt-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-[var(--ink-muted)]">Durasi {{ $booking->duration_days }} hari</span>
                        <span class="font-semibold text-[var(--navy-900)]">Rp {{ number_format((float) $booking->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-[var(--ink-muted)]">Biaya tambahan</span>
                        <span class="font-semibold text-[var(--navy-900)]">Rp 0</span>
                    </div>
                </div>

                @if ($payment->status === 'pending')
                    <div class="mt-4 space-y-2">
                        @if ($payment->gateway_url)
                            <a href="{{ $payment->gateway_url }}" target="_blank" rel="noopener" class="btn-primary-edu flex w-full items-center justify-center px-4 py-2.5 text-sm">
                                Buka Halaman Pembayaran
                            </a>
                        @endif
                        @if ($payment->payment_channel)
                            <a href="{{ route('booking.bayar', ['code' => $booking->code, 'metode' => $payment->payment_channel]) }}" class="btn-secondary-edu flex w-full items-center justify-center px-4 py-2.5 text-sm">
                                Lihat Instruksi Pembayaran
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="rounded-xl border border-dashed border-[var(--border)] p-6 text-center">
            <p class="font-semibold text-[var(--navy-900)]">Belum ada transaksi pembayaran</p>
            <p class="mt-1 text-sm">Tagihan akan muncul di sini setelah Anda memilih metode pembayaran.</p>
        </div>
    @endif
</section>
