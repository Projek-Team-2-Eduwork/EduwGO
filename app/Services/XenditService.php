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

        // 2. Kalkulasi Durasi & Load Relasi
        $booking->loadMissing(['user', 'vehicle']);
        $durationDays = $booking->start_at->diffInDays($booking->end_at) ?: 1;
        $invoiceDurationSeconds = setting('booking.invoice_minutes', 60) * 60;

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
            'expires_at' => Carbon::parse($data['expiry_date'])->setTimezone(config('app.timezone')),
            'gateway_payload' => $data,
            'amount' => $booking->total_amount,
        ]);
    }

    /**
     * Membuat Payment Request Xendit (QRIS / BANK_TRANSFER / EWALLET) untuk booking
     * pending lewat Payments API v3. Halaman bayar ditangani sendiri, bukan hosted
     * checkout (EG-52, format API dimigrasi EG-54).
     */
    public function createPaymentRequest(Booking $booking, string $channel = 'QRIS'): Payment
    {
        $channel = in_array($channel, ['QRIS', 'BANK_TRANSFER', 'EWALLET'], true) ? $channel : 'QRIS';

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

        // 2. Kalkulasi durasi & load relasi
        $booking->loadMissing(['user', 'vehicle']);
        $durationDays = $booking->start_at->diffInDays($booking->end_at) ?: 1;

        $payload = [
            'reference_id' => $booking->code,
            'type' => 'PAY',
            'country' => 'ID',
            'currency' => 'IDR',
            'request_amount' => (int) $booking->total_amount,
            'channel_code' => $this->channelCodeV3($channel),
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

        // 5. Masa berlaku: ikuti respons bila ada, kalau tidak pakai setting invoice_minutes
        $expiresAt = $data['channel_properties']['expires_at']
            ?? Carbon::now()->addMinutes((int) setting('booking.invoice_minutes', 60));

        // 6. Simpan log Payment (status booking tetap di BookingService, seperti invoice)
        return Payment::create([
            'booking_id' => $booking->id,
            'method' => 'xendit_payment_request',
            'status' => 'pending',
            'gateway_reference' => $data['payment_request_id'] ?? $data['id'] ?? null,
            'gateway_url' => null, // Tidak ada hosted checkout — halaman bayar sendiri
            'gateway_payload' => $data,
            'amount' => $booking->total_amount,
            'payment_channel' => $channel, // bucket pilihan UI (idempotensi per channel)
            'instructions' => $instructions,
            'expires_at' => Carbon::parse($expiresAt)->setTimezone(config('app.timezone')),
        ]);
    }

    /**
     * Menyusun instruksi pembayaran per bucket channel dari aksi respons
     * Payments API v3 (EG-52/54). String QR dipisahkan controller untuk kanvas.
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

        if ($channel === 'QRIS') {
            $steps = ['Scan QRIS dengan aplikasi bank atau e-wallet apa pun.'];
        } elseif ($channel === 'BANK_TRANSFER') {
            $bank = strtoupper(str_replace('_VIRTUAL_ACCOUNT', '', (string) ($data['channel_code'] ?? 'BCA')));
            $bankName = (string) ($data['channel_properties']['display_name'] ?? config('app.name'));

            $steps = [
                'Transfer sesuai nominal ke rekening berikut:',
                $bank.' '.((string) $vaNumber).' a.n. '.$bankName,
            ];
        } else {
            $steps = array_values(array_filter([
                $payUrl ? 'Selesaikan pembayaran lewat e-wallet: '.$payUrl : null,
                'Setelah pembayaran selesai, klik "Saya Sudah Bayar".',
            ]));
        }

        if ($qrString) {
            $steps[] = $qrString;
        }

        return $steps;
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
     * Memetakan bucket pilihan UI ke channel_code Xendit Payments API v3
     * (divalidasi langsung ke API test: QRIS, BCA_VIRTUAL_ACCOUNT, SHOPEEPAY).
     */
    private function channelCodeV3(string $channel): string
    {
        return match ($channel) {
            'BANK_TRANSFER' => 'BCA_VIRTUAL_ACCOUNT',
            'EWALLET' => 'SHOPEEPAY',
            default => 'QRIS',
        };
    }

    /**
     * Persyaratan channel_properties per channel_code v3: QRIS butuh masa
     * berlaku, VA butuh nama tampilan, e-wallet butuh URL kembalikan pembeli.
     *
     * @return array<string, string>
     */
    private function channelPropertiesV3(string $channel, Booking $booking): array
    {
        if ($channel === 'QRIS') {
            return [
                'expires_at' => now('UTC')->addMinutes((int) setting('booking.invoice_minutes', 60))->format('Y-m-d\TH:i:s\Z'),
            ];
        }

        if ($channel === 'BANK_TRANSFER') {
            return ['display_name' => (string) config('app.name')];
        }

        return [
            'success_return_url' => route('booking.success', $booking->code),
            'failure_return_url' => route('booking.failed', $booking->code),
        ];
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

            abort(404, 'Invoice Xendit tidak dikenal.');
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

            abort(404, 'Payment request Xendit tidak dikenal.');
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
