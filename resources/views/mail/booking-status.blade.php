<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Status Pesanan</title>
</head>
<body style="background-color: {{ App\Support\BrandTokens::value('--mail-bg') }}; font-family: Arial, sans-serif; padding: 20px; margin: 0; color: {{ App\Support\BrandTokens::value('--mail-ink') }};">
    <div style="max-width: 600px; margin: 0 auto; background-color: {{ App\Support\BrandTokens::value('--mail-surface') }}; padding: 30px; border-radius: 8px; box-shadow: {{ App\Support\BrandTokens::value('--mail-shadow') }};">

        @include('mail.partials.header')

        <h2 style="color: {{ App\Support\BrandTokens::value('--mail-heading') }}; margin-top: 0;">Halo, {{ $booking->customer_name }}!</h2>
        <p style="font-size: 16px; line-height: 1.5;">Status untuk pesanan <strong>{{ $booking->code }}</strong> Anda saat ini adalah: <strong>{{ $booking->status->label() }}</strong>.</p>

        @php($isCod = $booking->latestPayment?->payment_channel === App\Services\XenditService::COD)
        @if($booking->status === App\Enums\BookingStatus::Pending && $isCod)
            <p style="font-size: 16px; line-height: 1.5;">Pesanan Anda tercatat dengan metode bayar di tempat (COD). Siapkan pembayaran tunai saat mengambil kendaraan di cabang.</p>
            <p style="font-size: 14px; color: {{ App\Support\BrandTokens::value('--mail-muted') }}; text-align: center;">Batas bayar: {{ $booking->paymentDeadline()->translatedFormat('D, d M Y H:i') }}</p>
        @elseif($booking->status === App\Enums\BookingStatus::Pending)
            <p style="font-size: 16px; line-height: 1.5; color: {{ App\Support\BrandTokens::value('--mail-danger') }};">Pesanan Anda sedang menunggu pembayaran. Mohon selesaikan pembayaran sebelum batas waktu berakhir.</p>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $booking->latestPayment?->gateway_url ?? route('booking.waiting', $booking->code) }}" style="display: inline-block; background-color: {{ App\Support\BrandTokens::value('--mail-action') }}; color: {{ App\Support\BrandTokens::value('--mail-surface') }}; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold;">
                    Lanjut bayar
                </a>
            </div>

            @if($booking->latestPayment?->expires_at)
                <p style="font-size: 14px; color: {{ App\Support\BrandTokens::value('--mail-muted') }}; text-align: center;">Batas waktu: {{ $booking->latestPayment->expires_at->translatedFormat('D, d M Y H:i') }}</p>
            @endif
        @else
            <p style="font-size: 16px; line-height: 1.5;">{{ $booking->status->description() }}</p>
            @if($booking->payments->contains('needs_refund', true))
                <p style="font-size: 16px; line-height: 1.5;">Dana yang sudah Anda bayarkan akan dikembalikan oleh tim kami. Hubungi cabang di bawah bila ada pertanyaan.</p>
            @endif
        @endif

        <table style="width: 100%; margin-top: 20px; border-collapse: collapse; text-align: left;">
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; color: {{ App\Support\BrandTokens::value('--mail-muted') }};">Kendaraan</td>
                <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; font-weight: bold;">{{ $booking->vehicle->name ?? '-' }}</td>
            </tr>
            @include('mail.partials.location-row', ['branch' => $booking->vehicle?->branch])
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; color: {{ App\Support\BrandTokens::value('--mail-muted') }};">Jadwal Sewa</td>
                <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; font-weight: bold;">{{ $booking->start_at->translatedFormat('d M Y H:i') }} - {{ $booking->end_at->translatedFormat('d M Y H:i') }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; color: {{ App\Support\BrandTokens::value('--mail-muted') }};">Total Bayar</td>
                <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; font-weight: bold; color: {{ App\Support\BrandTokens::value('--mail-heading') }};">{{ rupiah($booking->total_amount) }}</td>
            </tr>
        </table>

        @include('mail.partials.footer', ['branch' => $booking->vehicle?->branch])
    </div>
</body>
</html>
