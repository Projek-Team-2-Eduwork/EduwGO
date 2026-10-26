<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pengingat Pesanan</title>
</head>
<body style="background-color: {{ App\Support\BrandTokens::value('--mail-bg') }}; font-family: Arial, sans-serif; padding: 20px; margin: 0; color: {{ App\Support\BrandTokens::value('--mail-ink') }};">
    <div style="max-width: 600px; margin: 0 auto; background-color: {{ App\Support\BrandTokens::value('--mail-surface') }}; padding: 30px; border-radius: 8px; box-shadow: {{ App\Support\BrandTokens::value('--mail-shadow') }};">

        @include('mail.partials.header')

        <h2 style="color: {{ App\Support\BrandTokens::value('--mail-heading') }}; margin-top: 0;">Halo, {{ $booking->customer_name }}!</h2>

        @if($type === 'pickup')
            <p style="font-size: 16px; line-height: 1.5;">Ini adalah pengingat bahwa Anda memiliki jadwal <strong>pengambilan kendaraan</strong> esok hari.</p>
        @else
            <p style="font-size: 16px; line-height: 1.5;">Ini adalah pengingat bahwa masa sewa Anda akan berakhir dan Anda memiliki jadwal <strong>pengembalian kendaraan</strong> esok hari.</p>
        @endif

        <table style="width: 100%; margin-top: 20px; border-collapse: collapse; text-align: left;">
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; color: {{ App\Support\BrandTokens::value('--mail-muted') }};">Kode Booking</td>
                <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; font-weight: bold;">{{ $booking->code }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; color: {{ App\Support\BrandTokens::value('--mail-muted') }};">Kendaraan</td>
                <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; font-weight: bold;">{{ $booking->vehicle->name ?? '-' }}</td>
            </tr>
            @include('mail.partials.location-row', ['branch' => $booking->vehicle?->branch, 'label' => $type === 'pickup' ? 'Lokasi ambil' : 'Lokasi pengembalian'])
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; color: {{ App\Support\BrandTokens::value('--mail-muted') }};">Waktu</td>
                <td style="padding: 10px; border-bottom: 1px solid {{ App\Support\BrandTokens::value('--mail-line') }}; font-weight: bold;">
                    @if($type === 'pickup')
                        {{ $booking->start_at->translatedFormat('l, d F Y - H:i') }}
                    @else
                        {{ $booking->end_at->translatedFormat('l, d F Y - H:i') }}
                    @endif
                </td>
            </tr>
        </table>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('booking.show', $booking->code) }}" style="display: inline-block; background-color: {{ App\Support\BrandTokens::value('--mail-action') }}; color: {{ App\Support\BrandTokens::value('--mail-surface') }}; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold;">
                Lihat Detail Pesanan
            </a>
        </div>

        @include('mail.partials.footer', ['branch' => $booking->vehicle?->branch])
    </div>
</body>
</html>
