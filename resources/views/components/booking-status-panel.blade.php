@props(['booking', 'payment' => null, 'label' => 'Status Pesanan'])

@php
    use App\Enums\BookingStatus;
    use App\Services\XenditService;

    $status = $booking->status;

    // Nada warna mengikuti BookingStatus::color() supaya konsisten dengan badge.
    $tone = match ($status->color()) {
        'success' => [
            'panel' => 'border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/40',
            'icon' => 'bg-emerald-600 text-white dark:bg-emerald-500 dark:text-emerald-950',
            'title' => 'text-emerald-900 dark:text-emerald-100',
            'text' => 'text-emerald-800 dark:text-emerald-200',
            'chip' => 'border-emerald-300 bg-white/70 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-100',
        ],
        'warning' => [
            'panel' => 'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40',
            'icon' => 'bg-amber-500 text-white dark:bg-amber-500 dark:text-amber-950',
            'title' => 'text-amber-900 dark:text-amber-100',
            'text' => 'text-amber-800 dark:text-amber-200',
            'chip' => 'border-amber-300 bg-white/70 text-amber-900 dark:border-amber-800 dark:bg-amber-900/40 dark:text-amber-100',
        ],
        'danger' => [
            'panel' => 'border-red-300 bg-red-50 dark:border-red-900 dark:bg-red-950/40',
            'icon' => 'bg-red-600 text-white dark:bg-red-500 dark:text-red-950',
            'title' => 'text-red-900 dark:text-red-100',
            'text' => 'text-red-800 dark:text-red-200',
            'chip' => 'border-red-300 bg-white/70 text-red-900 dark:border-red-900 dark:bg-red-900/40 dark:text-red-100',
        ],
        'info' => [
            'panel' => 'border-sky-300 bg-sky-50 dark:border-sky-900 dark:bg-sky-950/40',
            'icon' => 'bg-sky-600 text-white dark:bg-sky-500 dark:text-sky-950',
            'title' => 'text-sky-900 dark:text-sky-100',
            'text' => 'text-sky-800 dark:text-sky-200',
            'chip' => 'border-sky-300 bg-white/70 text-sky-900 dark:border-sky-900 dark:bg-sky-900/40 dark:text-sky-100',
        ],
        default => [
            'panel' => 'border-slate-300 bg-slate-100 dark:border-slate-700 dark:bg-slate-800/60',
            'icon' => 'bg-slate-600 text-white dark:bg-slate-500 dark:text-slate-950',
            'title' => 'text-slate-900 dark:text-slate-100',
            'text' => 'text-slate-700 dark:text-slate-300',
            'chip' => 'border-slate-300 bg-white/70 text-slate-900 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-100',
        ],
    };

    // Satu keterangan waktu paling relevan menurut status pesanan.
    $waktu = match ($status) {
        BookingStatus::Pending => $payment?->expires_at ? 'Batas bayar '.$payment->expires_at->translatedFormat('D, d M Y H:i') : null,
        BookingStatus::Paid => $payment?->paid_at ? 'Dibayar '.$payment->paid_at->translatedFormat('D, d M Y H:i') : null,
        BookingStatus::Rented => 'Jatuh tempo '.$booking->end_at->translatedFormat('D, d M Y H:i'),
        BookingStatus::Returned => 'Selesai '.$booking->end_at->translatedFormat('D, d M Y H:i'),
        BookingStatus::Expired => $payment?->expires_at ? 'Batas bayar terlewat '.$payment->expires_at->translatedFormat('D, d M Y H:i') : null,
        BookingStatus::Cancelled => null,
    };

    $metode = $payment ? XenditService::paymentLabel($payment->payment_channel, $payment->method) : null;
@endphp

<section aria-label="{{ $label }}" class="rounded-xl border p-5 {{ $tone['panel'] }}">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $tone['icon'] }}">
                <x-status-icon :status="$status" class="h-6 w-6" />
            </span>

            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] {{ $tone['text'] }}">{{ $label }}</p>
                <h3 class="mt-0.5 text-xl font-extrabold {{ $tone['title'] }}">{{ $status->headline() }}</h3>
                <p class="mt-1 max-w-xl text-sm {{ $tone['text'] }}">{{ $status->description() }}</p>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @if ($waktu)
                        <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold {{ $tone['chip'] }}">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ $waktu }}
                        </span>
                    @endif

                    @if ($status === BookingStatus::Paid && $metode)
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $tone['chip'] }}">
                            {{ $metode }}
                        </span>
                    @endif

                    @if ($status === BookingStatus::Cancelled && $booking->cancel_reason)
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $tone['chip'] }}">
                            Alasan: {{ $booking->cancel_reason }}
                        </span>
                    @endif

                    @if ($payment?->status === 'failed')
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $tone['chip'] }}">
                            Pembayaran gagal
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex shrink-0 flex-col items-stretch gap-3 lg:items-end">
            <div class="rounded-xl border px-4 py-3 text-left lg:text-right {{ $tone['chip'] }}">
                <p class="text-[11px] font-bold uppercase tracking-wider">{{ $status === BookingStatus::Paid ? 'Total Dibayar' : 'Total Tagihan' }}</p>
                <p class="text-xl font-black {{ $tone['title'] }}">Rp {{ number_format((float) $booking->total_amount, 0, ',', '.') }}</p>
            </div>

            @if ($status === BookingStatus::Pending)
                <a href="{{ route('booking.waiting', $booking->code) }}" class="btn-primary-edu inline-flex items-center justify-center px-5 py-2.5 text-sm">
                    Bayar Sekarang
                </a>
            @elseif (in_array($status, [BookingStatus::Cancelled, BookingStatus::Expired], true))
                <a href="{{ route('booking.rebook', $booking->code) }}" class="btn-secondary-edu inline-flex items-center justify-center px-5 py-2.5 text-sm">
                    Pesan Ulang
                </a>
            @endif
        </div>
    </div>
</section>
