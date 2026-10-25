@props(['booking', 'payment' => null])
<section aria-label="Ringkasan pembayaran">
    <h3 class="text-lg font-bold">Ringkasan pembayaran</h3>
    <dl class="receipt mt-3 text-sm">
        <div><dt>Sewa {{ $booking->duration_days }} hari</dt><dd>{{ rupiah($booking->total_amount) }}</dd></div>
        <div><dt>Pembayaran</dt><dd>{{ payment_label($payment) }}</dd></div>
        @if($payment?->paid_at)
            <div><dt>Dibayar pada</dt><dd>{{ $payment->paid_at->translatedFormat('d M Y, H:i') }}</dd></div>
        @endif
        <div class="font-bold"><dt>Total</dt><dd>{{ rupiah($booking->total_amount) }}</dd></div>
    </dl>
</section>
