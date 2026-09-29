<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_booking_pending_ubah_status_dan_history_sistem()
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'pending',
            'gateway_reference' => 'inv_111',
            'expires_at' => now()->subMinutes(5),
        ]);

        $this->artisan('bookings:expire')->assertSuccessful();

        $booking->refresh();
        $this->assertEquals(BookingStatus::Expired, $booking->status);

        $payment = $booking->latestPayment;
        $this->assertEquals('expired', $payment->status);

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'to_status' => BookingStatus::Expired->value,
            'changed_by' => null, // Diproses oleh aksi Sistem
        ]);
    }

    public function test_booking_belum_expired_tetap_pending()
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'pending',
            'gateway_reference' => 'inv_222',
            'expires_at' => now()->addHour(),
        ]);

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertEquals(BookingStatus::Pending, $booking->fresh()->status);

        $this->assertDatabaseMissing('booking_status_histories', [
            'booking_id' => $booking->id,
            'to_status' => BookingStatus::Expired->value,
        ]);
    }

    public function test_booking_sudah_paid_tidak_disentuh()
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Paid]);
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'paid',
            'gateway_reference' => 'inv_333',
            'expires_at' => now()->subHour(),
        ]);

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertEquals(BookingStatus::Paid, $booking->fresh()->status);
    }
}
