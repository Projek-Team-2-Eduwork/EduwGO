<?php

namespace Tests\Feature\Security;

use App\Enums\BookingStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BookingService::transition() mengecek ulang status dari baris DB yang dikunci,
 * bukan dari instance model yang mungkin sudah basi (proses lain sudah mengubahnya).
 */
class BookingTransitionRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transisi_valid_tetap_berjalan_dan_tercatat(): void
    {
        $booking = Booking::factory()->pending()->create();

        app(BookingService::class)->transition($booking, BookingStatus::Paid, null, 'Pembayaran via Xendit');

        $this->assertSame(BookingStatus::Paid, $booking->refresh()->status);
        $this->assertSame(1, $booking->histories()->where('to_status', 'paid')->count());
    }

    public function test_instance_basi_ditolak_bila_status_di_db_sudah_berubah(): void
    {
        $booking = Booking::factory()->pending()->create();

        // Dua proses memuat booking yang sama saat masih Pending.
        $stale = Booking::findOrFail($booking->id);
        app(BookingService::class)->transition($booking, BookingStatus::Paid);

        try {
            app(BookingService::class)->transition($stale, BookingStatus::Expired);
            $this->fail('Transisi dari instance basi seharusnya ditolak.');
        } catch (InvalidTransitionException) {
            // Diharapkan: status di DB sudah Paid, Paid → Expired tidak sah.
        }

        $this->assertSame(BookingStatus::Paid, $booking->refresh()->status);
        $this->assertSame(0, $booking->histories()->where('to_status', 'expired')->count());
    }

    public function test_dua_payment_lunas_untuk_booking_sama_hanya_satu_transisi(): void
    {
        $booking = Booking::factory()->pending()->create();

        $payments = collect(['pr_qris', 'pr_va'])->map(fn (string $ref) => Payment::create([
            'booking_id' => $booking->id,
            'method' => 'xendit_payment_request',
            'status' => 'pending',
            'gateway_reference' => $ref,
            'amount' => $booking->total_amount,
            'expires_at' => now()->addHour(),
        ]));

        // Payment kedua memegang relasi booking yang dimuat sebelum payment pertama lunas.
        $payments[1]->load('booking');

        app(PaymentService::class)->applyInvoiceStatus($payments[0], ['status' => 'PAID']);
        app(PaymentService::class)->applyInvoiceStatus($payments[1], ['status' => 'PAID']);

        $this->assertSame(BookingStatus::Paid, $booking->refresh()->status);
        $this->assertSame(1, $booking->histories()->where('to_status', 'paid')->count());
    }
}
