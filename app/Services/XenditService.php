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
     * Memproses payload webhook dari Xendit: cari payment lalu terapkan status invoice.
     */
    public function handleWebhook(array $payload): void
    {
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
}
