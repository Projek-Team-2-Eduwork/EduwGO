<?php

namespace App\Listeners;

use App\Enums\BookingStatus;
use App\Events\BookingStatusChanged;
use App\Models\Payment;
use App\Services\XenditService;
use Illuminate\Support\Facades\DB;

/**
 * Begitu booking keluar dari Pending (lunas, expired, atau batal), semua payment
 * lain yang masih pending ditutup: lokal ditandai expired di transaksi yang sama,
 * lalu instrumennya di Xendit dibatalkan setelah commit supaya QR/VA lama tidak
 * bisa dibayar lagi. Sengaja tidak di-queue: status lokal harus konsisten.
 */
class ExpirePendingPayments
{
    public function __construct(private XenditService $xenditService) {}

    public function handle(BookingStatusChanged $event): void
    {
        if ($event->oldStatus !== BookingStatus::Pending || $event->newStatus === BookingStatus::Pending) {
            return;
        }

        $payments = Payment::where('booking_id', $event->booking->id)
            ->where('status', 'pending')
            ->lockForUpdate()
            ->get();

        if ($payments->isEmpty()) {
            return;
        }

        foreach ($payments as $payment) {
            $payment->status = 'expired';
            $payment->save();
        }

        // Panggilan HTTP di luar transaksi: gagal batal di gateway tidak boleh
        // menggagalkan perubahan status (PAID susulan tetap ditandai perlu refund).
        DB::afterCommit(function () use ($payments) {
            foreach ($payments as $payment) {
                $this->xenditService->cancelGatewayPayment($payment);
            }
        });
    }
}
