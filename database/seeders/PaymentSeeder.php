<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Pastikan tiap booking punya baris payment,
     * dan pembayaran lunas memiliki data gateway (invoice).
     */
    public function run(): void
    {
        foreach (Booking::query()->get() as $booking) {
            $payment = $booking->payments()->first();

            if ($payment === null) {
                $status = match ($booking->status) {
                    'pending' => 'pending',
                    'expired' => 'expired',
                    'cancelled' => 'failed',
                    default => 'paid',
                };

                $payment = $booking->payments()->create([
                    'method' => 'xendit_invoice',
                    'amount' => $booking->total_amount,
                    'status' => $status,
                    'expires_at' => now()->addHours(24),
                    'paid_at' => $status === 'paid' ? now() : null,
                ]);
            }

            if ($payment->status === 'paid' && $payment->gateway_reference === null) {
                $reference = 'inv_'.str_pad((string) $payment->id, 10, '0', STR_PAD_LEFT);

                $payment->update([
                    'gateway_reference' => $reference,
                    'gateway_url' => 'https://checkout.xendit.co/'.$reference,
                    'gateway_payload' => ['id' => $reference, 'status' => 'PAID', 'seeded' => true],
                ]);
            }
        }
    }
}
