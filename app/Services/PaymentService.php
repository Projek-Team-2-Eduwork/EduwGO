<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
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
     *
     * Status payment monoton: paid bersifat terminal dan tidak pernah diturunkan
     * oleh event EXPIRED/FAILED yang datang belakangan (cron, sync, webhook lama).
     */
    public function applyInvoiceStatus(Payment $payment, array $payload): void
    {
        $status = strtoupper($payload['status'] ?? '');

        DB::transaction(function () use ($payment, $payload, $status) {
            // Urutan kunci selalu booking → payment (sama dengan cron & transition)
            // supaya webhook dan scheduler yang jalan bersamaan tidak deadlock.
            $booking = $this->lockedBooking($payment);
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status === 'paid') {
                // Event lanjutan untuk payment yang sudah paid: simpan payload hanya
                // bila sama-sama PAID, agar bukti pembayaran tidak ditimpa EXPIRED.
                if (in_array($status, ['PAID', 'SETTLED'], true)) {
                    $payment->gateway_payload = $payload;
                    $payment->save();
                }

                $this->logApplied($payment, $status);

                return;
            }

            // Simpan raw payload setiap event
            $payment->gateway_payload = $payload;

            if (in_array($status, ['PAID', 'SETTLED'], true)) {
                $payment->status = 'paid';
                $payment->paid_at = isset($payload['paid_at'])
                    ? Carbon::parse($payload['paid_at'])->setTimezone(config('app.timezone'))
                    : now();

                if ($booking->status === BookingStatus::Pending) {
                    $payment->save();
                    $this->bookingService->transition($booking, BookingStatus::Paid, null, 'Pembayaran via Xendit');
                } else {
                    // Uang masuk untuk booking yang sudah Expired/Cancelled/lunas lewat
                    // payment lain. Booking lama tidak dihidupkan otomatis (unit bisa
                    // sudah dipesan orang lain); admin refund manual.
                    $payment->needs_refund = true;
                    $payment->save();

                    Log::channel('xendit')->warning('Pembayaran diterima untuk booking non-Pending, perlu refund.', [
                        'payment_id' => $payment->id,
                        'booking_code' => $booking->code,
                        'booking_status' => $booking->status->value,
                        'amount' => $payment->amount,
                    ]);
                }
            } elseif ($status === 'EXPIRED') {
                if ($payment->status !== 'expired') {
                    $payment->status = 'expired';
                }

                $payment->save();

                // Satu payment kedaluwarsa belum tentu berarti booking habis waktu:
                // penyewa masih boleh pilih metode lain sampai batas bayar booking.
                if ($booking->status === BookingStatus::Pending && $booking->isPaymentDeadlinePassed()) {
                    $this->bookingService->transition($booking, BookingStatus::Expired, null, 'Invoice kedaluwarsa');
                }
            } else {
                // PENDING atau status lainnya: cukup simpan payload tanpa mengubah status
                $payment->save();
            }

            $this->logApplied($payment, $status);
        });
    }

    /**
     * Expire booking Pending yang batas bayarnya sudah lewat. Status dicek ulang
     * setelah baris booking dikunci: webhook PAID yang commit lebih dulu menang.
     * Payment pending ikut ditutup oleh listener ExpirePendingPayments.
     */
    public function expireIfDeadlinePassed(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== BookingStatus::Pending || ! $locked->isPaymentDeadlinePassed()) {
                return false;
            }

            $this->bookingService->transition($locked, BookingStatus::Expired, null, 'Batas waktu pembayaran habis');

            return true;
        });
    }

    private function logApplied(Payment $payment, string $status): void
    {
        Log::channel('xendit')->info('Status invoice Xendit diterapkan.', [
            'payment_id' => $payment->id,
            'gateway_reference' => $payment->gateway_reference,
            'status' => $status,
        ]);
    }

    /**
     * Booking milik payment dengan kunci baris: satu booking bisa punya beberapa
     * payment (ganti metode bayar), jadi kunci payment saja tidak cukup untuk
     * mencegah dua event berbeda membaca status Pending yang sama.
     */
    private function lockedBooking(Payment $payment): Booking
    {
        return Booking::whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();
    }
}
