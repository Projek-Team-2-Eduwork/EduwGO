<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pengingat Pesanan</title>
</head>
<body style="background-color: #f9fafb; font-family: Arial, sans-serif; padding: 20px; margin: 0; color: #374151;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        
        @include('mail.partials.header')

        <h2 style="color: #111827; margin-top: 0;">Halo, {{ $booking->customer_name }}!</h2>
        
        @if($type === 'pickup')
            <p style="font-size: 16px; line-height: 1.5;">Ini adalah pengingat bahwa Anda memiliki jadwal <strong>pengambilan kendaraan</strong> esok hari.</p>
        @else
            <p style="font-size: 16px; line-height: 1.5;">Ini adalah pengingat bahwa masa sewa Anda akan berakhir dan Anda memiliki jadwal <strong>pengembalian kendaraan</strong> esok hari.</p>
        @endif

        <table style="width: 100%; margin-top: 20px; border-collapse: collapse; text-align: left;">
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Kode Booking</td>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; font-weight: bold;">{{ $booking->code }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Kendaraan</td>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; font-weight: bold;">{{ $booking->vehicle->name ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Waktu</td>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; font-weight: bold;">
                    @if($type === 'pickup')
                        {{ $booking->start_at->translatedFormat('l, d F Y - H:i') }}
                    @else
                        {{ $booking->end_at->translatedFormat('l, d F Y - H:i') }}
                    @endif
                </td>
            </tr>
        </table>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('booking.show', $booking->code) }}" style="display: inline-block; background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold;">
                Lihat Detail Pesanan
            </a>
        </div>

        <div style="margin-top: 40px; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 20px;">
            <p style="font-size: 14px; color: #9ca3af; margin: 0;">{{ setting('contact.address', 'Alamat belum diatur') }}</p>
            <p style="font-size: 14px; color: #9ca3af; margin: 5px 0 0;">Jam Operasional: {{ setting('contact.hours', '-') }}</p>
        </div>
    </div>
</body>
</html>