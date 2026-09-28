<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditService
{
    public function __construct(
        private BookingService $bookingService
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
     * Memproses payload webhook dari Xendit secara idempotent.
     */
    public function handleWebhook(array $payload): void
    {
        $invoiceId = $payload['id'] ?? null;
        $externalId = $payload['external_id'] ?? null;
        $status = strtoupper($payload['status'] ?? '');

        DB::transaction(function () use ($invoiceId, $externalId, $status, $payload) {
            $payment = null;

            // 1. Cari berdasarkan gateway_reference (id invoice)
            if ($invoiceId) {
                $payment = Payment::where('gateway_reference', $invoiceId)
                    ->lockForUpdate()
                    ->first();
            }

            // 2. Fallback cari melalui kode booking (external_id) jika by ID invoice tidak ketemu
            if (! $payment && $externalId) {
                $booking = Booking::where('code', $externalId)->first();
                if ($booking) {
                    $payment = $booking->payments()
                        ->where('method', 'xendit_invoice')
                        ->latest()
                        ->lockForUpdate()
                        ->first();
                }
            }

            // 3. Jika tetap tidak ada, log warning dan hentikan eksekusi (tetap return 200 nantinya)
            if (! $payment) {
                Log::warning('Webhook Xendit diterima untuk invoice yang tidak dikenal.', [
                    'invoice_id' => $invoiceId,
                    'external_id' => $externalId,
                ]);

                return;
            }

            // 4. Selalu simpan raw payload
            $payment->gateway_payload = $payload;

            // 5. Evaluasi status dengan pendekatan idempotent
            if (in_array($status, ['PAID', 'SETTLED'])) {
                if ($payment->status !== 'paid') {
                    $payment->status = 'paid';
                    $payment->paid_at = isset($payload['paid_at'])
                        ? Carbon::parse($payload['paid_at'])->setTimezone(config('app.timezone'))
                        : now();
                    $payment->save();

                    $booking = $payment->booking;
                    if ($booking->status === BookingStatus::Pending) {
                        $this->bookingService->transition(
                            $booking,
                            BookingStatus::Paid,
                            null,
                            'Pembayaran via Xendit (webhook)'
                        );
                    }
                } else {
                    $payment->save(); // Status sudah paid, cukup simpan update payload
                }
            } elseif ($status === 'EXPIRED') {
                if ($payment->status !== 'expired') {
                    $payment->status = 'expired';
                    $payment->save();

                    $booking = $payment->booking;
                    if ($booking->status === BookingStatus::Pending) {
                        $this->bookingService->transition(
                            $booking,
                            BookingStatus::Expired,
                            null,
                            'Invoice kedaluwarsa (webhook Xendit)'
                        );
                    }
                } else {
                    $payment->save(); // Status sudah expired, cukup simpan update payload
                }
            } else {
                // PENDING atau status lainnya, cukup simpan payload tanpa mengubah status pembayaran/booking
                $payment->save();
            }
        });
    }
}
