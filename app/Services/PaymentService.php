<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private BookingService $bookingService
    ) {}

    /**
     * Menerapkan status invoice Xendit (PAID/SETTLED/EXPIRED) ke payment & booking
     * secara idempotent. Dipakai webhook (EG-14) dan sinkronisasi manual (EG-26).
     */
    public function applyInvoiceStatus(Payment $payment, array $payload): void
    {
        $status = strtoupper($payload['status'] ?? '');

        DB::transaction(function () use ($payment, $payload, $status) {
            // Kunci baris payment — aman dari pengiriman webhook ganda bersamaan
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            // Simpan raw payload setiap event
            $payment->gateway_payload = $payload;

            if (in_array($status, ['PAID', 'SETTLED'])) {
                // Idempotent: payment sudah paid → cukup simpan payload
                if ($payment->status !== 'paid') {
                    $payment->status = 'paid';
                    $payment->paid_at = isset($payload['paid_at'])
                        ? Carbon::parse($payload['paid_at'])->setTimezone(config('app.timezone'))
                        : now();
                    $payment->save();

                    $booking = $payment->booking;
                    if ($booking->status === BookingStatus::Pending) {
                        $this->bookingService->transition($booking, BookingStatus::Paid, null, 'Pembayaran via Xendit');
                    }
                } else {
                    $payment->save();
                }
            } elseif ($status === 'EXPIRED') {
                // Idempotent: payment sudah expired → cukup simpan payload
                if ($payment->status !== 'expired') {
                    $payment->status = 'expired';
                    $payment->save();

                    $booking = $payment->booking;
                    if ($booking->status === BookingStatus::Pending) {
                        $this->bookingService->transition($booking, BookingStatus::Expired, null, 'Invoice kedaluwarsa');
                    }
                } else {
                    $payment->save();
                }
            } else {
                // PENDING atau status lainnya: cukup simpan payload tanpa mengubah status
                $payment->save();
            }

            Log::channel('xendit')->info('Status invoice Xendit diterapkan.', [
                'payment_id' => $payment->id,
                'gateway_reference' => $payment->gateway_reference,
                'status' => $status,
            ]);
        });
    }
}
