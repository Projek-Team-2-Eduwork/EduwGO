<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditService
{
    /**
     * Versi skema API untuk endpoint /v3/payment_requests (wajib via header).
     */
    private const API_VERSION = '2024-11-11';

    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Create or retrieve an existing invoice for a pending booking.
     */
    public function createInvoice(Booking $booking): Payment
    {
        // Mode Payment Request (halaman bayar sendiri, EG-52) — cabang ke Invoice API
        // lama hanya saat mode 'invoice', sehingga rollback mudah lewat config.
        if (config('services.xendit.mode', 'invoice') === 'payment_request') {
            return $this->createPaymentRequest($booking);
        }

        // 1. Idempotensi: Gunakan invoice pending yang masih berlaku
        $existingPayment = Payment::where('booking_id', $booking->id)
            ->where('method', 'xendit_invoice')
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($existingPayment) {
            return $existingPayment;
        }

        // 2. Kalkulasi Durasi & Load Relasi. Masa berlaku invoice = sisa waktu
        // sampai batas bayar booking, jadi tidak bisa memperpanjang hold unit.
        $booking->loadMissing(['user', 'vehicle']);
        $durationDays = $booking->start_at->diffInDays($booking->end_at) ?: 1;
        $invoiceDurationSeconds = $this->remainingSeconds($booking);

        $payload = [
            'external_id' => $booking->code,
            'amount' => $booking->total_amount,
            'payer_email' => $booking->user->email,
            'description' => "Sewa {$booking->vehicle->name} {$durationDays} hari",
            'invoice_duration' => $invoiceDurationSeconds,
            'success_redirect_url' => route('booking.success', $booking->code),
            'failure_redirect_url' => route('booking.failed', $booking->code),
            'currency' => 'IDR',
        ];

        // 3. Request ke Xendit
        $secretKey = config('services.xendit.secret_key', env('XENDIT_SECRET_KEY'));

        $response = Http::withBasicAuth($secretKey, '')
            ->post('https://api.xendit.co/v2/invoices', $payload);

        if (! $response->successful()) {
            throw new Exception('Gagal membuat invoice Xendit: '.$response->body());
        }

        $data = $response->json();

        // 4. Simpan log Payment (Status Booking tidak diubah di sini, tetap di BookingService)
        return Payment::create([
            'booking_id' => $booking->id,
            'method' => 'xendit_invoice',
            'status' => 'pending',
            'gateway_reference' => $data['id'],
            'gateway_url' => $data['invoice_url'],
            'expires_at' => $this->capToDeadline($booking, $data['expiry_date'] ?? null),
            'gateway_payload' => $data,
            'amount' => $booking->total_amount,
        ]);
    }

    /**
     * Katalog channel pembayaran yang didukung (divalidasi ke API Xendit demo).
     * Tiap channel_code dipetakan ke kategori tampilan + label pada halaman bayar
     * (EG-55, desain ulang bergaya gateway).
     *
     * @var array<string, array{category: string, label: string}>
     */
    public const CHANNELS = [
        'BCA_VIRTUAL_ACCOUNT' => ['category' => 'Virtual Account', 'label' => 'BCA Virtual Account'],
        'BNI_VIRTUAL_ACCOUNT' => ['category' => 'Virtual Account', 'label' => 'BNI Virtual Account'],
        'BRI_VIRTUAL_ACCOUNT' => ['category' => 'Virtual Account', 'label' => 'BRI Virtual Account'],
        'MANDIRI_VIRTUAL_ACCOUNT' => ['category' => 'Virtual Account', 'label' => 'Mandiri Virtual Account'],
        'PERMATA_VIRTUAL_ACCOUNT' => ['category' => 'Virtual Account', 'label' => 'Permata Virtual Account'],
        'CIMB_VIRTUAL_ACCOUNT' => ['category' => 'Virtual Account', 'label' => 'CIMB Niaga Virtual Account'],
        'CREDIT_CARD' => ['category' => 'Kartu Kredit', 'label' => 'Visa / Mastercard'],
        'SHOPEEPAY' => ['category' => 'E-Wallet', 'label' => 'ShopeePay'],
        'DANA' => ['category' => 'E-Wallet', 'label' => 'DANA'],
        'OVO' => ['category' => 'E-Wallet', 'label' => 'OVO'],
        'LINKAJA' => ['category' => 'E-Wallet', 'label' => 'LinkAja!'],
        'ASTRAPAY' => ['category' => 'E-Wallet', 'label' => 'AstraPay'],
        'GOPAY' => ['category' => 'E-Wallet', 'label' => 'GoPay'],
        'QRIS' => ['category' => 'QR', 'label' => 'QRIS'],
        'ALFAMART' => ['category' => 'Retail Outlet', 'label' => 'Alfamart'],
        'INDOMARET' => ['category' => 'Retail Outlet', 'label' => 'Indomaret'],
    ];

    /**
     * Menyusun katalog channel per kategori untuk ditampilkan halaman bayar
     * (EG-55, gaya gateway). Tiap channel membawa channel_code (untuk link
     * ?metode=), label tampilan, dan logo brand SVG inline (EG-56).
     *
     * @return array<int, array{name: string, channels: array<int, array{code: string, label: string, logo: array<int, string>, icon: string}>}>
     */
    public static function channelCatalog(): array
    {
        $logos = self::channelLogos();
        $files = self::channelLogoFiles();

        $grouped = [];

        foreach (self::CHANNELS as $code => $meta) {
            $urls = self::channelLogoUrls($code);

            $grouped[$meta['category']][] = [
                'code' => $code,
                'label' => $meta['label'],
                'logo' => $urls,
                'icon' => $logos[$code] ?? $logos['QRIS'],
            ];
        }

        $order = ['Virtual Account', 'Kartu Kredit', 'E-Wallet', 'QR', 'Retail Outlet'];
        $catalog = [];

        foreach ($order as $kategori) {
            if (! isset($grouped[$kategori])) {
                continue;
            }

            $catalog[] = [
                'name' => $kategori,
                'channels' => $grouped[$kategori],
            ];
        }

        return $catalog;
    }

    /**
     * Logo brand per channel_code (chip putih pada halaman bayar, EG-56).
     * Ditulis sebagai SVG inline (tanpa aset eksternal) agar tetap tampil
     * saat offline dan ikut ter-cache blade.
     *
     * @return array<string, string>
     */
    public static function channelLogos(): array
    {
        return [
            'BCA_VIRTUAL_ACCOUNT' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="BCA"><text x="28" y="23" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="16" font-weight="700" font-style="italic" fill="#0060AF">BCA</text></svg>',
            'BNI_VIRTUAL_ACCOUNT' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="BNI"><text x="28" y="23" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="14" font-weight="800" fill="#F15A22">BNI</text></svg>',
            'BRI_VIRTUAL_ACCOUNT' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="BRI"><text x="28" y="23" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="14" font-weight="800" fill="#00529C">BRI</text></svg>',
            'MANDIRI_VIRTUAL_ACCOUNT' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Mandiri"><text x="28" y="23" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="11" font-weight="700" fill="#003A70">mandiri</text></svg>',
            'PERMATA_VIRTUAL_ACCOUNT' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Permata"><text x="28" y="23" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="11" font-weight="700" fill="#0064FF">Permata</text></svg>',
            'CIMB_VIRTUAL_ACCOUNT' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="CIMB Niaga"><text x="28" y="23" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="10" font-weight="700" fill="#790008">CIMB Niaga</text></svg>',
            'CREDIT_CARD' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Visa Mastercard"><circle cx="22" cy="18" r="11" fill="#EB001B"/><circle cx="34" cy="18" r="11" fill="#F79E1B"/><ellipse cx="28" cy="18" rx="5" ry="9.2" fill="#FF5F00"/></svg>',
            'SHOPEEPAY' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="ShopeePay"><text x="28" y="22" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="10" font-weight="700" fill="#EE4D2D">ShopeePay</text></svg>',
            'DANA' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="DANA"><text x="28" y="23" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="14" font-weight="800" fill="#118EEA">DANA</text></svg>',
            'OVO' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="OVO"><text x="28" y="23" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="15" font-weight="800" fill="#4C3494">OVO</text></svg>',
            'LINKAJA' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="LinkAja"><text x="28" y="23" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="12" font-weight="700" fill="#E31E24">LinkAja</text></svg>',
            'ASTRAPAY' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="AstraPay"><text x="28" y="23" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="11" font-weight="700" fill="#0A3A85">AstraPay</text></svg>',
            'GOPAY' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="GoPay"><text x="28" y="23" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="14" font-weight="800" fill="#00AED6">gopay</text></svg>',
            'QRIS' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="QRIS"><g fill="#E31E24"><rect x="14" y="7" width="9" height="9" rx="1.5"/><rect x="16.5" y="9.5" width="4" height="4" fill="#fff"/><rect x="33" y="7" width="9" height="9" rx="1.5"/><rect x="35.5" y="9.5" width="4" height="4" fill="#fff"/><rect x="14" y="20" width="9" height="9" rx="1.5"/><rect x="16.5" y="22.5" width="4" height="4" fill="#fff"/><rect x="33" y="20" width="4" height="4"/><rect x="39" y="20" width="4" height="4"/><rect x="33" y="26" width="4" height="4"/><rect x="39" y="26" width="4" height="4"/></g></svg>',
            'ALFAMART' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Alfamart"><text x="28" y="23" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="11" font-weight="700" font-style="italic" fill="#E31E24">Alfamart</text></svg>',
            'INDOMARET' => '<svg class="h-full w-full" viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Indomaret"><text x="28" y="23" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="10" font-weight="700" fill="#E31E24">Indomaret</text></svg>',
        ];
    }

    /**
     * File logo resmi (relatif ke folder public) per channel_code, EG-56.
     * Bila salah satu file tidak ada, halaman bayar otomatis jatuh ke
     * SVG inline dari channelLogos() agar tetap tampil.
     *
     * @return array<string, array<int, string>>
     */
    public static function channelLogoFiles(): array
    {
        return [
            'BCA_VIRTUAL_ACCOUNT' => ['images/payment/bca.svg'],
            'BNI_VIRTUAL_ACCOUNT' => ['images/payment/bni.svg'],
            'BRI_VIRTUAL_ACCOUNT' => ['images/payment/bri.svg'],
            'MANDIRI_VIRTUAL_ACCOUNT' => ['images/payment/mandiri.svg'],
            'PERMATA_VIRTUAL_ACCOUNT' => ['images/payment/permata.svg'],
            'CIMB_VIRTUAL_ACCOUNT' => ['images/payment/cimb-niaga.svg'],
            'CREDIT_CARD' => ['images/payment/visa.svg', 'images/payment/mastercard.svg'],
            'SHOPEEPAY' => ['images/payment/shopeepay.svg'],
            'DANA' => ['images/payment/dana.svg'],
            'OVO' => ['images/payment/ovo.svg'],
            'LINKAJA' => ['images/payment/linkaja.svg'],
            'ASTRAPAY' => ['images/payment/astrapay.png'],
            'GOPAY' => ['images/payment/gopay.svg'],
            'QRIS' => ['images/payment/qris.svg'],
            'ALFAMART' => ['images/payment/alfamart.svg'],
            'INDOMARET' => ['images/payment/indomaret.svg'],
        ];
    }

    /**
     * URL logo brand sebuah channel_code yang filenya benar-benar ada
     * (dipakai chip logo di halaman bayar & detail pesanan). Kosong bila
     * channel-nya tidak dikenal atau file logo belum tersedia.
     *
     * @return array<int, string>
     */
    public static function channelLogoUrls(?string $channel): array
    {
        $urls = [];

        foreach (self::channelLogoFiles()[$channel] ?? [] as $file) {
            if (is_file(public_path($file))) {
                $urls[] = asset($file);
            }
        }

        return $urls;
    }

    /**
     * Label tampilan untuk satu baris Payment di halaman detail pesanan:
     * pakai nama channel bila ada (mis. "BCA Virtual Account"), selain itu
     * jatuh balik ke label metode pembayarannya (Tunai, Xendit Invoice, dsb).
     */
    public static function paymentLabel(?string $channel, ?string $method): string
    {
        if ($channel !== null && isset(self::CHANNELS[$channel])) {
            return self::CHANNELS[$channel]['label'];
        }

        return match ($method) {
            'cash' => 'Tunai (bayar di kasir)',
            'xendit_invoice' => 'Xendit Invoice',
            'xendit_payment_request' => 'Xendit Payment Request',
            null, '' => 'Metode belum dipilih',
            default => ucwords(str_replace('_', ' ', $method)),
        };
    }

    /**
     * Membuat Payment Request Xendit untuk booking pending lewat Payments API v3.
     * Halaman bayar ditangani sendiri, bukan hosted checkout (EG-52, format API
     * dimigrasi EG-54). Channel mengikuti katalog CHANNELS (EG-55); nilai bucket
     * lama QRIS/BANK_TRANSFER/EWALLET tetap diterima demi kompatibilitas URL lama.
     */
    public function createPaymentRequest(Booking $booking, string $channel = 'QRIS'): Payment
    {
        $channel = $this->resolveChannel($channel);

        // 1. Idempotensi: pakai payment request pending yang masih berlaku (per channel)
        $existingPayment = Payment::where('booking_id', $booking->id)
            ->where('method', 'xendit_payment_request')
            ->where('payment_channel', $channel)
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($existingPayment) {
            return $existingPayment;
        }

        // 2. Batas bayar booking tetap: payment baru tidak boleh dibuat setelahnya
        $this->remainingSeconds($booking);

        // 3. Kalkulasi durasi & load relasi
        $booking->loadMissing(['user', 'vehicle']);
        $durationDays = $booking->start_at->diffInDays($booking->end_at) ?: 1;

        $payload = [
            'reference_id' => $booking->code,
            'type' => 'PAY',
            'country' => 'ID',
            'currency' => 'IDR',
            'request_amount' => (int) $booking->total_amount,
            'channel_code' => $channel,
            'channel_properties' => $this->channelPropertiesV3($channel, $booking),
            'description' => "Sewa {$booking->vehicle->name} {$durationDays} hari",
            'metadata' => [
                'booking_id' => $booking->id,
                'booking_code' => $booking->code,
            ],
        ];

        // 3. Request ke Xendit Payments API v3 (header api-version wajib)
        $secretKey = config('services.xendit.secret_key', env('XENDIT_SECRET_KEY'));

        $response = Http::withBasicAuth($secretKey, '')
            ->withHeaders(['api-version' => self::API_VERSION])
            ->post('https://api.xendit.co/v3/payment_requests', $payload);

        if (! $response->successful()) {
            throw new Exception('Gagal membuat payment request Xendit: '.$response->body());
        }

        $data = $response->json();
        $actions = is_array($data['actions'] ?? null) ? $data['actions'] : [];

        // 4. Instruksi untuk UI bayar disusun dari aksi respons (QR / VA / tautan)
        $instructions = $this->buildPaymentInstructions($channel, $actions, $data);

        // 5. Masa berlaku: ikuti respons bila ada, tapi tidak pernah melewati batas bayar booking
        $expiresAt = $this->capToDeadline($booking, $data['channel_properties']['expires_at'] ?? null);

        // 6. Simpan log Payment (status booking tetap di BookingService, seperti invoice)
        return Payment::create([
            'booking_id' => $booking->id,
            'method' => 'xendit_payment_request',
            'status' => 'pending',
            'gateway_reference' => $data['payment_request_id'] ?? $data['id'] ?? null,
            'gateway_url' => null, // Tidak ada hosted checkout — halaman bayar sendiri
            'gateway_payload' => $data,
            'amount' => $booking->total_amount,
            'payment_channel' => $channel, // channel_code pilihan UI (idempotensi per channel)
            'instructions' => $instructions,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Menyusun instruksi pembayaran per channel_code dari aksi respons Payments
     * API v3 (EG-52/54). String QR dipisahkan controller untuk kanvas.
     *
     * @param  array<int, mixed>  $actions
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function buildPaymentInstructions(string $channel, array $actions, array $data): array
    {
        $qrString = $this->actionValue($actions, 'QR_STRING');
        $vaNumber = $this->actionValue($actions, 'VIRTUAL_ACCOUNT_NUMBER');
        $payUrl = $this->actionValue($actions, 'DEEPLINK_URL') ?? $this->actionValue($actions, 'WEB_URL');
        $paymentCode = $this->actionValue($actions, 'PAYMENT_CODE');
        $label = self::CHANNELS[$channel]['label'] ?? $channel;

        // QRIS: langkah scan + string QR (dipisah ke kanvas saat ditampilkan)
        if ($channel === 'QRIS') {
            return array_values(array_filter([
                'Scan QRIS dengan aplikasi bank atau e-wallet apa pun.',
                'Pilih menu Scan / Bayar QRIS di aplikasi tersebut, lalu arahkan kamera ke QR di halaman ini.',
                'Pastikan nama merchant dan nominalnya sudah sesuai, lalu konfirmasi dengan PIN kamu.',
                'Pembayaran terverifikasi otomatis. Kalau statusnya belum berubah, klik "Saya Sudah Bayar".',
                $qrString,
            ]));
        }

        // Virtual Account: nomor rekening + langkah transfer per bank
        if (str_contains($channel, '_VIRTUAL_ACCOUNT')) {
            $bank = str_replace(' Virtual Account', '', $label);
            $bankName = (string) ($data['channel_properties']['display_name'] ?? config('app.name'));

            return array_values(array_filter([
                'Buka m-banking '.$bank.' (menu Transfer → Virtual Account) atau ATM '.$bank.'.',
                'Masukkan nomor Virtual Account berikut:',
                $bank.' '.((string) $vaNumber).' a.n. '.$bankName,
                'Masukkan nominal persis sesuai total pembayaran, lalu selesaikan transaksinya.',
                'Simpan bukti transfer, lalu klik "Saya Sudah Bayar".',
            ]));
        }

        // Retail outlet (Alfamart / Indomaret): kode bayar ditunjukkan ke kasir
        if ($paymentCode) {
            return array_values(array_filter([
                'Datang ke gerai '.$label.' terdekat atau buka aplikasi '.$label.'.',
                'Sebutkan kode pembayaran berikut kepada kasir:',
                'Kode pembayaran: '.$paymentCode,
                'Bayar tunai sebesar total pembayaran, lalu simpan struknya sebagai bukti.',
                'Setelah bayar, klik "Saya Sudah Bayar".',
            ]));
        }

        // E-wallet: langkah di aplikasi + tautan pembayaran (tombol di halaman bayar)
        return array_values(array_filter([
            'Buka aplikasi '.$label.' di HP kamu.',
            $payUrl
                ? 'Tekan tombol "Buka Pembayaran '.$label.'" di halaman ini, lalu lanjutkan di aplikasi.'
                : 'Pilih menu Bayar, lalu masukkan nominal persis sesuai total pembayaran.',
            'Konfirmasi pembayaran dan masukkan PIN '.$label.' kamu.',
            'Setelah selesai, klik "Saya Sudah Bayar".',
        ]));
    }

    /**
     * Menyiapkan tampilan halaman bayar (langkah, QR, tautan tombol) dari payload
     * tersimpan. Langkah selalu dihitung ulang dari aksi gateway supaya baris
     * pembayaran lama ikut memakai kalimat instruksi terbaru (EG-55).
     *
     * Catatan: e-wallet seperti ShopeePay di sandbox ikut mengirim aksi QR_STRING.
     * QR hanya ditampilkan untuk kanal QRIS agar metode lain tidak terlihat
     * seperti memakai QRIS.
     *
     * @return array{steps: array<int, string>, qr_string: string|null, payment_url: string|null}
     */
    public function paymentPresentation(Payment $payment): array
    {
        $channel = (string) $payment->payment_channel;
        $payload = $payment->gateway_payload;

        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        $payload = is_array($payload) ? $payload : [];
        $actions = is_array($payload['actions'] ?? null) ? $payload['actions'] : [];
        $stored = is_array($payment->instructions ?? null) ? $payment->instructions : [];

        $steps = $actions === [] ? $stored : $this->buildPaymentInstructions($channel, $actions, $payload);

        $rawQr = $this->actionValue($actions, 'QR_STRING');
        $qrString = $channel === 'QRIS' ? $rawQr : null;
        $paymentUrl = $channel === 'QRIS'
            ? null
            : ($this->actionValue($actions, 'DEEPLINK_URL') ?? $this->actionValue($actions, 'WEB_URL'));

        $bersih = [];

        foreach ($steps as $step) {
            if (! is_string($step)) {
                continue;
            }

            // String QR jangan ikut jadi butir langkah (QRIS tampil lewat kanvas,
            // kanal lain tidak menampilkan QR sama sekali).
            if ($rawQr !== null && $step === $rawQr) {
                continue;
            }

            // QR EMV pada baris pembayaran lama dipindah ke kanvas QR.
            if ($channel === 'QRIS' && str_starts_with($step, '000201')) {
                $qrString ??= $step;

                continue;
            }

            $bersih[] = $step;
        }

        return [
            'steps' => array_values($bersih),
            'qr_string' => $qrString,
            'payment_url' => $paymentUrl,
        ];
    }

    /**
     * Kartu kredit/debit dialihkan ke halaman checkout Xendit (Invoice API v2).
     * Payments API v3 menolak CREDIT_CARD untuk negara ID karena wajib mengirim
     * card_details (cakupan PCI-DSS), jadi form kartu tetap di-host Xendit.
     */
    public function createCardInvoice(Booking $booking): Payment
    {
        // 1. Idempotensi: pakai invoice kartu pending yang masih berlaku
        $existingPayment = Payment::where('booking_id', $booking->id)
            ->where('method', 'xendit_invoice')
            ->where('payment_channel', 'CREDIT_CARD')
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($existingPayment) {
            return $existingPayment;
        }

        // 2. Kalkulasi durasi & load relasi
        $booking->loadMissing(['user', 'vehicle']);
        $durationDays = $booking->start_at->diffInDays($booking->end_at) ?: 1;

        $payload = [
            'external_id' => $booking->code,
            'amount' => $booking->total_amount,
            'payer_email' => $booking->user->email,
            'description' => "Sewa {$booking->vehicle->name} {$durationDays} hari",
            'invoice_duration' => $this->remainingSeconds($booking),
            'success_redirect_url' => route('booking.success', $booking->code),
            'failure_redirect_url' => route('booking.failed', $booking->code),
            'currency' => 'IDR',
            'payment_methods' => ['CREDIT_CARD'],
        ];

        // 3. Request ke Xendit Invoice API v2
        $secretKey = config('services.xendit.secret_key', env('XENDIT_SECRET_KEY'));

        $response = Http::withBasicAuth($secretKey, '')
            ->post('https://api.xendit.co/v2/invoices', $payload);

        if (! $response->successful()) {
            throw new Exception('Gagal membuat invoice kartu Xendit: '.$response->body());
        }

        $data = $response->json();

        // 4. Simpan log Payment (status booking tetap di BookingService)
        return Payment::create([
            'booking_id' => $booking->id,
            'method' => 'xendit_invoice',
            'status' => 'pending',
            'gateway_reference' => $data['id'],
            'gateway_url' => $data['invoice_url'],
            'gateway_payload' => $data,
            'amount' => $booking->total_amount,
            'payment_channel' => 'CREDIT_CARD',
            'expires_at' => $this->capToDeadline($booking, $data['expiry_date'] ?? null),
        ]);
    }

    /**
     * Sisa detik sampai batas bayar booking. Kurang dari 1 menit dianggap habis:
     * payment baru ditolak supaya hold unit tidak bisa diperpanjang.
     */
    private function remainingSeconds(Booking $booking): int
    {
        $remaining = (int) floor(now()->diffInSeconds($booking->paymentDeadline(), false));

        if ($remaining < 60) {
            throw new Exception("Batas waktu pembayaran booking {$booking->code} sudah habis.");
        }

        return $remaining;
    }

    /**
     * Masa berlaku payment lokal: dari respons gateway bila ada, dibatasi batas bayar booking.
     */
    private function capToDeadline(Booking $booking, ?string $gatewayExpiry): Carbon
    {
        $deadline = Carbon::instance($booking->paymentDeadline())->setTimezone(config('app.timezone'));

        if ($gatewayExpiry === null) {
            return $deadline;
        }

        $expiresAt = Carbon::parse($gatewayExpiry)->setTimezone(config('app.timezone'));

        return $expiresAt->greaterThan($deadline) ? $deadline : $expiresAt;
    }

    /**
     * Membatalkan instrumen pembayaran di Xendit (booking sudah tidak Pending):
     * invoice v2 lewat endpoint expire!, payment request v3 lewat cancel.
     * Best effort: kegagalan hanya dicatat, PAID susulan tetap ditandai perlu refund.
     */
    public function cancelGatewayPayment(Payment $payment): void
    {
        $secretKey = config('services.xendit.secret_key');

        if (! $payment->gateway_reference || ! $secretKey) {
            return;
        }

        try {
            $response = match ($payment->method) {
                'xendit_invoice' => Http::withBasicAuth($secretKey, '')
                    ->timeout(10)
                    ->post('https://api.xendit.co/invoices/'.$payment->gateway_reference.'/expire!'),
                'xendit_payment_request' => Http::withBasicAuth($secretKey, '')
                    ->withHeaders(['api-version' => self::API_VERSION])
                    ->timeout(10)
                    ->post('https://api.xendit.co/v3/payment_requests/'.$payment->gateway_reference.'/cancel'),
                default => null,
            };
        } catch (Exception $e) {
            Log::channel('xendit')->warning('Gagal membatalkan payment di Xendit.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($response !== null && ! $response->successful()) {
            Log::channel('xendit')->warning('Gagal membatalkan payment di Xendit.', [
                'payment_id' => $payment->id,
                'http' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    /**
     * Mengambil nilai aksi respons v3 berdasarkan deskriptor (QR_STRING, dst).
     *
     * @param  array<int, mixed>  $actions
     */
    private function actionValue(array $actions, string $descriptor): ?string
    {
        foreach ($actions as $action) {
            if (is_array($action) && ($action['descriptor'] ?? null) === $descriptor && ! empty($action['value'])) {
                return (string) $action['value'];
            }
        }

        return null;
    }

    /**
     * Menyelesaikan nilai ?metode= menjadi channel_code yang ada di katalog.
     * Nilai bucket lawas QRIS/BANK_TRANSFER/EWALLET dipetakan ke channel_code
     * sekarang; nilai tak dikenal jatuh balik ke QRIS (default).
     */
    private function resolveChannel(string $channel): string
    {
        $alias = match ($channel) {
            'BANK_TRANSFER' => 'BCA_VIRTUAL_ACCOUNT',
            'EWALLET' => 'SHOPEEPAY',
            default => $channel,
        };

        return isset(self::CHANNELS[$alias]) ? $alias : 'QRIS';
    }

    /**
     * Persyaratan channel_properties per channel_code v3 (divalidasi ke API demo):
     * QRIS butuh masa berlaku, VA butuh nama tampilan, e-wallet butuh URL kembalikan
     * pembeli (OVO + nomor HP), retail butuh nama pembayar.
     *
     * @return array<string, string>
     */
    private function channelPropertiesV3(string $channel, Booking $booking): array
    {
        if ($channel === 'QRIS') {
            return [
                'expires_at' => $booking->paymentDeadline()->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            ];
        }

        if (str_contains($channel, '_VIRTUAL_ACCOUNT')) {
            return ['display_name' => (string) config('app.name')];
        }

        if ($channel === 'ALFAMART' || $channel === 'INDOMARET') {
            return ['payer_name' => $booking->user->name ?? 'Penyewa'];
        }

        $returnUrls = [
            'success_return_url' => route('booking.success', $booking->code),
            'failure_return_url' => route('booking.failed', $booking->code),
        ];

        // GoPay mewajibkan URL pembatalan di samping URL sukses/gagal
        if ($channel === 'GOPAY') {
            $returnUrls['cancel_return_url'] = route('booking.failed', $booking->code);
        }

        // OVO mewajibkan nomor HP pembeli dalam format E.164 (+62..., tanpa nol depan)
        if ($channel === 'OVO') {
            // Hanya format lokal 08xx yang diubah ke +62; nomor lain sudah berkode negara.
            $phone = preg_replace('/\D/', '', (string) ($booking->user->phone ?? ''));

            if (str_starts_with($phone, '0')) {
                $phone = '62'.ltrim($phone, '0');
            }

            if ($phone !== '') {
                $returnUrls['account_mobile_number'] = '+'.$phone;
            }
        }

        return $returnUrls;
    }

    /**
     * Memproses payload webhook dari Xendit: cari payment lalu terapkan status invoice.
     */
    public function handleWebhook(array $payload): void
    {
        // Event Payment Request (halaman bayar sendiri, EG-52/54):
        // Payments API v3 → payment.capture / payment.authorization / payment.failure,
        // versi lama tetap diterima → payment.paid / payment.failed
        $event = (string) ($payload['event'] ?? '');
        if (in_array($event, ['payment.paid', 'payment.failed', 'payment.capture', 'payment.authorization', 'payment.failure'], true)) {
            $this->handlePaymentRequestEvent($event, $payload);

            return;
        }

        $invoiceId = $payload['id'] ?? null;
        $externalId = $payload['external_id'] ?? null;

        // Cari payment berdasarkan gateway_reference (id invoice)
        $payment = $invoiceId ? Payment::where('gateway_reference', $invoiceId)->first() : null;

        if (! $payment) {
            Log::channel('xendit')->warning('Webhook Xendit: invoice tidak dikenal.', [
                'invoice_id' => $invoiceId,
                'external_id' => $externalId,
            ]);

            // Event terotentikasi tapi tak dikenal (mis. ping uji dashboard atau
            // invoice lama) → akui dengan 200 agar Xendit tidak mengulang kirim
            return;
        }

        // Logika status dikumpulkan di PaymentService agar bisa dipakai EG-26
        $this->paymentService->applyInvoiceStatus($payment, $payload);
    }

    /**
     * Handler event Payment Request (EG-52/54): semua event didelegasikan ke
     * PaymentService::applyInvoiceStatus (idempotent, EG-14) dengan bentuk payload
     * diseragamkan ke gaya Invoice API:
     *  - payment.capture (v3) / payment.paid (lama) → status PAID + paid_at dari
     *    captures[0].capture_timestamp → booking jadi paid
     *  - payment.failure (v3) / payment.failed (lama) → status FAILED (cabang
     *    "lainnya"): payload dicatat, status payment & booking TIDAK berubah
     *  - payment.authorization → status AUTHORIZED: payload dicatat saja (tanpa bayar)
     */
    private function handlePaymentRequestEvent(string $event, array $payload): void
    {
        // Webhook Xendit membungkus isi di key "data" (fallback: payload datar)
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        // v3 memakai data.payment_request_id, versi lama memakai data.id
        $reference = $data['payment_request_id'] ?? $data['id'] ?? null;

        $payment = $reference ? Payment::where('gateway_reference', $reference)->first() : null;

        if (! $payment) {
            Log::channel('xendit')->warning('Webhook Xendit: payment request tidak dikenal.', [
                'event' => $event,
                'reference' => $reference,
            ]);

            // Event terotentikasi tapi referensi tak dikenal (mis. ping uji
            // dashboard Xendit) → akui 200, jangan 404 (biar tak diulang Xendit)
            return;
        }

        $paid = in_array($event, ['payment.paid', 'payment.capture'], true);
        $failed = in_array($event, ['payment.failed', 'payment.failure'], true);

        $this->paymentService->applyInvoiceStatus($payment, array_merge($data, [
            'event' => $event,
            'status' => $paid ? 'PAID' : ($failed ? 'FAILED' : strtoupper((string) ($data['status'] ?? ''))),
            'paid_at' => $paid
                ? ($data['paid_at']
                    ?? $data['succeeded_at']
                    ?? $data['captures'][0]['capture_timestamp']
                    ?? $data['updated']
                    ?? null)
                : null,
        ]));
    }

    /**
     * Menyelaraskan status pembayaran dengan API Xendit tanpa menunggu
     * webhook. Dipakai polling "Saya Sudah Bayar" (EG-56), halaman berhasil,
     * dan tombol Cek Status admin. Aman dipanggil berkali-kali — idempoten.
     *
     * Semua payment pending booking dicek (penyewa bisa membuka beberapa metode),
     * baik payment request v3 maupun invoice v2. Mengembalikan false bila ada
     * panggilan API yang gagal (detail hanya di log).
     */
    public function syncPaymentStatus(Booking $booking): bool
    {
        $payments = Payment::where('booking_id', $booking->id)
            ->whereIn('method', ['xendit_payment_request', 'xendit_invoice'])
            ->where('status', 'pending')
            ->whereNotNull('gateway_reference')
            ->latest('id')
            ->get();

        $ok = true;

        foreach ($payments as $payment) {
            // Payment lain bisa sudah ditutup oleh payment sebelumnya yang ternyata PAID
            if (Payment::whereKey($payment->id)->value('status') !== 'pending') {
                continue;
            }

            $ok = ($payment->method === 'xendit_payment_request'
                ? $this->syncPaymentRequestStatus($payment)
                : $this->syncInvoiceStatus($payment)) && $ok;
        }

        return $ok;
    }

    /**
     * Menyelaraskan satu payment request (mode payment_request) — jalur utama.
     */
    private function syncPaymentRequestStatus(Payment $payment): bool
    {
        if (config('services.xendit.mode', 'invoice') !== 'payment_request') {
            return true;
        }

        try {
            $data = $this->getPaymentRequest($payment->gateway_reference);
        } catch (Exception $e) {
            // Gagal cek bukan hal fatal — biarkan webhook yang jadi sumber kebenaran
            Log::channel('xendit')->warning('Gagal menyelaraskan status payment request.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        $status = strtoupper((string) ($data['status'] ?? ''));
        $map = ['SUCCEEDED' => 'PAID', 'FAILED' => 'FAILED', 'EXPIRED' => 'EXPIRED'];

        if (! isset($map[$status])) {
            return true; // REQUIRES_ACTION / PENDING / lainnya → belum bayar
        }

        $this->paymentService->applyInvoiceStatus($payment, [
            'status' => $map[$status],
            'paid_at' => $data['succeeded_at']
                ?? $data['payment']['paid_at']
                ?? $data['updated']
                ?? null,
        ]);

        return true;
    }

    /**
     * Sinkron satu payment invoice (kartu kredit / mode invoice)
     * lewat GET /v2/invoices/{id} — pelengkap webhook.
     */
    private function syncInvoiceStatus(Payment $payment): bool
    {
        try {
            $invoice = $this->getInvoice($payment->gateway_reference);
        } catch (Exception $e) {
            Log::channel('xendit')->warning('Gagal menyelaraskan status invoice.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        $status = strtoupper((string) ($invoice['status'] ?? ''));

        if (! in_array($status, ['PAID', 'SETTLED', 'EXPIRED'], true)) {
            return true;
        }

        $this->paymentService->applyInvoiceStatus($payment, [
            'status' => $status,
            'paid_at' => $invoice['paid_at'] ?? null,
        ]);

        return true;
    }

    /**
     * Mengambil detail payment request dari Payments API v3 berdasarkan ID.
     *
     * @return array<string, mixed>
     */
    private function getPaymentRequest(string $id): array
    {
        $secretKey = config('services.xendit.secret_key', env('XENDIT_SECRET_KEY'));

        $response = Http::withBasicAuth($secretKey, '')
            ->withHeaders(['api-version' => self::API_VERSION])
            ->get('https://api.xendit.co/v3/payment_requests/'.$id);

        if (! $response->successful()) {
            throw new Exception('Gagal mengambil status payment request Xendit: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Simulasikan pembayaran sukses untuk payment request (test mode).
     * Hanya untuk key xnd_devel — di production mengembalikan false.
     */
    public function simulatePaymentRequest(string $reference, int $amount): bool
    {
        if (! str_starts_with((string) config('services.xendit.secret_key'), 'xnd_devel')) {
            return false;
        }

        $response = Http::withBasicAuth(config('services.xendit.secret_key'), '')
            ->withHeaders(['api-version' => '2024-11-11'])
            ->timeout(10)
            ->post("https://api.xendit.co/v3/payment_requests/{$reference}/simulate", [
                'amount' => $amount,
            ]);

        if (! $response->successful()) {
            Log::channel('xendit')->warning('Simulasi pembayaran demo gagal.', [
                'reference' => $reference,
                'http' => $response->status(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Mendapatkan detail invoice dari API Xendit berdasarkan reference ID.
     */
    public function getInvoice(string $reference): array
    {
        $secretKey = config('services.xendit.secret_key', env('XENDIT_SECRET_KEY'));

        $response = Http::withBasicAuth($secretKey, '')
            ->get('https://api.xendit.co/v2/invoices/'.$reference);

        if (! $response->successful()) {
            throw new Exception('Gagal mengambil status invoice Xendit: '.$response->body());
        }

        return $response->json();
    }
}
