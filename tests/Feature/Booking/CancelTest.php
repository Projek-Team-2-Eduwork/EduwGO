<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancel_pending_sukses()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->pending()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('booking.cancel', $booking->code));

        $response->assertRedirect();
        $this->assertEquals(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'to_status' => BookingStatus::Cancelled->value,
            'note' => 'Dibatalkan penyewa',
        ]);
    }

    public function test_cancel_sudah_batal_atau_paid_mengembalikan_403()
    {
        $user = User::factory()->create();
        $bookingCancelled = Booking::factory()->cancelled()->create(['user_id' => $user->id]);
        $bookingPaid = Booking::factory()->paid()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('booking.cancel', $bookingCancelled->code))->assertForbidden();
        $this->actingAs($user)->post(route('booking.cancel', $bookingPaid->code))->assertForbidden();
    }

    public function test_cancel_oleh_bukan_pemilik_mengembalikan_403()
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $booking = Booking::factory()->pending()->create(['user_id' => $owner->id]);

        $this->actingAs($attacker)->post(route('booking.cancel', $booking->code))->assertForbidden();
    }
}
