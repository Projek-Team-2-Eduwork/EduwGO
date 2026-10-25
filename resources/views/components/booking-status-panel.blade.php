@props(['booking', 'payment' => null, 'label' => 'Status pesanan'])
<section aria-label="{{ $label }}" class="border-b border-[var(--line)] pb-6">
    <p class="text-sm">{{ $booking->code }}</p>
    <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
        <div class="max-w-xl">
            <h1 class="text-2xl">{{ $booking->status->label() }}</h1>
            <p class="mt-2 text-sm leading-6">{{ $booking->status->description() }}</p>
            @if($booking->status === App\Enums\BookingStatus::Pending && $payment?->expires_at)
                <p class="mt-2 text-sm font-semibold">Batas bayar {{ $payment->expires_at->translatedFormat('d M Y, H:i') }}</p>
            @endif
            @if($booking->status === App\Enums\BookingStatus::Cancelled && $booking->cancel_reason)
                <p class="mt-2 text-sm">Alasan: {{ $booking->cancel_reason }}</p>
            @endif
        </div>
        @if($booking->status === App\Enums\BookingStatus::Pending)
            <a href="{{ route('booking.waiting', $booking->code) }}" class="btn-primary-edu inline-flex items-center">Lanjut bayar</a>
        @elseif(in_array($booking->status, [App\Enums\BookingStatus::Cancelled, App\Enums\BookingStatus::Expired], true))
            <a href="{{ route('booking.rebook', $booking->code) }}" class="btn-primary-edu inline-flex items-center">Pesan ulang</a>
        @endif
    </div>
</section>
