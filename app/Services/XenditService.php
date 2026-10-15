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
            'gateway_payload' => json_encode($data),
            'amount' => $booking->total_amount,
        ]);
    }

    /**
     * Membuat Payment Request Xendit (QRIS / BANK_TRANSFER / EWALLET) untuk booking
     * pending. Halaman bayar ditangani sendiri, bukan hosted checkout (EG-52).
     */
    public function createPaymentRequest(Booking $booking, string $channel = 'QRIS'): Payment
    {
        $channel = in_array($channel, ['QRIS', 'BANK_TRANSFER', 'EWALLET'], true) ? $channel : 'QRIS';

        // 1. Idempotensi: pakai payment request pending yang masih berlaku
        $existingPayment = Payment::where('booking_id', $booking->id)
            ->where('method', 'xendit_payment_request')
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
            'amount' => (int) $booking->total_amount,
            'currency' => 'IDR',
            'description' => "Sewa {$booking->vehicle->name} {$durationDays} hari",
            'country' => 'ID',
            'payment_method' => $this->paymentMethodPayload($channel, $booking),
            'metadata' => [
                'booking_id' => $booking->id,
                'booking_code' => $booking->code,
            ],
        ];

        // 3. Request ke Xendit
        $secretKey = config('services.xendit.secret_key', env('XENDIT_SECRET_KEY'));

        $response = Http::withBasicAuth($secretKey, '')
            ->withHeaders(['api-version' => '2022-07-31'])
            ->post('https://api.xendit.co/payment_requests', $payload);

        if (! $response->successful()) {
            throw new Exception('Gagal membuat payment request Xendit: '.$response->body());
        }

        $data = $response->json();
        $method = $data['payment_method'] ?? [];

        // 4. Instruksi untuk UI bayar: ikuti respons Xendit bila ada, kalau tidak susun sendiri
        $instructions = $data['instructions']
            ?? $method['instructions']
            ?? $this->buildPaymentInstructions($channel, $method);

        // 5. Masa berlaku: ikuti respons bila ada, kalau tidak pakai setting invoice_minutes
        $expiresAt = $method['qr_code']['expires_at']
            ?? $data['expires_at']
            ?? Carbon::now()->addMinutes((int) setting('booking.invoice_minutes', 60));

        // 6. Simpan log Payment (status booking tetap di BookingService, seperti invoice)
        return Payment::create([
            'booking_id' => $booking->id,
            'method' => 'xendit_payment_request',
            'status' => 'pending',
            'gateway_reference' => $data['id'],
            'gateway_url' => null, // Tidak ada hosted checkout — halaman bayar sendiri
            'gateway_payload' => json_encode($data),
            'amount' => $booking->total_amount,
            'payment_channel' => $channel,
            'instructions' => $instructions,
            'expires_at' => Carbon::parse($expiresAt)->setTimezone(config('app.timezone')),
        ]);
    }

    /**
     * Objek payment_method format Payment Request v3 per channel.
     *
     * @return array<string, mixed>
     */
    private function paymentMethodPayload(string $channel, Booking $booking): array
    {
        return match ($channel) {
            'BANK_TRANSFER' => [
                'type' => 'VIRTUAL_ACCOUNT',
                'reusability' => 'ONE_TIME_USE',
                'virtual_account' => [
                    'channel_code' => 'BCA',
                    'channel_properties' => ['customer_name' => $booking->user?->name ?? 'Pelanggan'],
                ],
            ],
            'EWALLET' => [
                'type' => 'EWALLET',
                'reusability' => 'ONE_TIME_USE',
                'ewallet' => [
                    'channel_code' => 'DANA',
                    'channel_properties' => [
                        'success_return_url' => url('/'),
                        'failure_return_url' => url('/'),
                    ],
                ],
            ],
            default => [
                'type' => 'QR_CODE',
                'reusability' => 'ONE_TIME_USE',
                'qr_code' => ['channel_code' => 'QRIS'],
            ],
        };
    }

    /**
     * Menyusun instruksi pembayaran per channel dari data payment_method (EG-52).
     *
     * @return array<int, string>
     */
    private function buildPaymentInstructions(string $channel, array $method): array
    {
        if ($channel === 'QRIS') {
            $qr = $method['qr_code'] ?? [];

            return array_values(array_filter([
                'Scan QRIS dengan aplikasi bank atau e-wallet apa pun.',
                $qr['qr_string'] ?? $qr['channel_properties']['qr_string'] ?? null,
            ]));
        }

        if ($channel === 'BANK_TRANSFER') {
            $va = $method['virtual_account'] ?? [];

            return array_values(array_filter([
                'Transfer sesuai nominal ke virtual account berikut:',
                ($va['channel_code'] ?? '').' '.($va['channel_properties']['virtual_account_number'] ?? ''),
            ]));
        }

        $ewallet = $method['ewallet'] ?? [];

        return array_values(array_filter([
            'Bayar lewat e-wallet: '.($ewallet['ewallet_type'] ?? ''),
            $ewallet['reference'] ?? null,
        ]));
    }

    /**
     * Memproses payload webhook dari Xendit: cari payment lalu terapkan status invoice.
     */
    public function handleWebhook(array $payload): void
    {
        // Event Payment Request (halaman bayar sendiri): payment.paid & payment.failed (EG-52)
        $event = (string) ($payload['event'] ?? '');
        if (in_array($event, ['payment.paid', 'payment.failed'], true)) {
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
     * Handler event Payment Request (EG-52): semua event didelegasikan ke
     * PaymentService::applyInvoiceStatus (idempotent, EG-14) dengan bentuk payload
     * diseragamkan ke gaya Invoice API:
     *  - payment.paid  → status PAID + paid_at dari succeeded_at → booking jadi paid
     *  - payment.failed → status FAILED (cabang "lainnya"): payload dicatat,
     *    status payment & booking TIDAK berubah (tetap pending)
     */
    private function handlePaymentRequestEvent(string $event, array $payload): void
    {
        // Webhook Xendit membungkus isi di key "data" (fallback: payload datar)
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        $reference = $data['id'] ?? null;

        $payment = $reference ? Payment::where('gateway_reference', $reference)->first() : null;

        if (! $payment) {
            Log::channel('xendit')->warning('Webhook Xendit: payment request tidak dikenal.', [
                'event' => $event,
                'reference' => $reference,
            ]);

            abort(404, 'Payment request Xendit tidak dikenal.');
        }

        $this->paymentService->applyInvoiceStatus($payment, array_merge($data, [
            'event' => $event,
            'status' => $event === 'payment.paid' ? 'PAID' : 'FAILED',
            'paid_at' => $event === 'payment.paid'
                ? ($data['succeeded_at'] ?? $data['paid_at'] ?? null)
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
