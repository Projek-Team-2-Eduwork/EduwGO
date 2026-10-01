<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Status Pesanan</title>
</head>
<body style="background-color: #f9fafb; font-family: Arial, sans-serif; padding: 20px; margin: 0; color: #374151;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        
        @include('mail.partials.header')

        <h2 style="color: #111827; margin-top: 0;">Halo, {{ $booking->customer_name }}!</h2>
        <p style="font-size: 16px; line-height: 1.5;">Status untuk pesanan <strong>{{ $booking->code }}</strong> Anda saat ini adalah: <strong style="text-transform: uppercase;">{{ $booking->status->value }}</strong>.</p>

        @if($booking->status === App\Enums\BookingStatus::Pending)
            <p style="font-size: 16px; line-height: 1.5; color: #b91c1c;">Pesanan Anda sedang menunggu pembayaran. Mohon selesaikan pembayaran sebelum batas waktu berakhir.</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $booking->latestPayment?->gateway_url ?? route('booking.waiting', $booking->code) }}" style="display: inline-block; background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold;">
                    Bayar Sekarang
                </a>
            </div>
            
            @if($booking->latestPayment?->expires_at)
                <p style="font-size: 14px; color: #6b7280; text-align: center;">Batas waktu: {{ $booking->latestPayment->expires_at->translatedFormat('D, d M Y H:i') }}</p>
            @endif
        @elseif($booking->status === App\Enums\BookingStatus::Paid)
            <p style="font-size: 16px; line-height: 1.5;">Pembayaran Anda telah berhasil kami terima. Silakan persiapkan diri Anda untuk jadwal pengambilan.</p>
        @elseif($booking->status === App\Enums\BookingStatus::Expired)
            <p style="font-size: 16px; line-height: 1.5;">Batas waktu pembayaran pesanan Anda telah habis.</p>
        @elseif($booking->status === App\Enums\BookingStatus::Cancelled)
            <p style="font-size: 16px; line-height: 1.5;">Pesanan Anda telah dibatalkan.</p>
        @endif

        <table style="width: 100%; margin-top: 20px; border-collapse: collapse; text-align: left;">
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Kendaraan</td>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; font-weight: bold;">{{ $booking->vehicle->name ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Jadwal Sewa</td>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; font-weight: bold;">{{ $booking->start_at->translatedFormat('d M Y H:i') }} - {{ $booking->end_at->translatedFormat('d M Y H:i') }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Total Bayar</td>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; font-weight: bold; color: #111827;">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div style="margin-top: 40px; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 20px;">
            <p style="font-size: 14px; color: #9ca3af; margin: 0;">{{ setting('contact.address', 'Alamat belum diatur') }}</p>
            <p style="font-size: 14px; color: #9ca3af; margin: 5px 0 0;">Jam Operasional: {{ setting('contact.hours', '-') }}</p>
        </div>
    </div>
</body>
</html>