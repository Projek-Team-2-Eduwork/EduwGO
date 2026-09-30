<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingDaftarPesananTest extends TestCase
{
    use RefreshDatabase;

    public function test_1_isolasi_data_hanya_melihat_booking_milik_sendiri()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $bookingA = Booking::factory()->create(['user_id' => $userA->id, 'start_at' => now(), 'duration_days' => 1]);
        $bookingB = Booking::factory()->create(['user_id' => $userB->id, 'start_at' => now(), 'duration_days' => 1]);

        $response = $this->actingAs($userA)->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee($bookingA->code);
        $response->assertDontSee($bookingB->code);
    }

    public function test_2_filter_status_berfungsi_dan_abaikan_filter_invalid()
    {
        $user = User::factory()->create();
        $bookingPending = Booking::factory()->create(['user_id' => $user->id, 'status' => BookingStatus::Pending, 'start_at' => now(), 'duration_days' => 1]);
        $bookingPaid = Booking::factory()->create(['user_id' => $user->id, 'status' => BookingStatus::Paid, 'start_at' => now(), 'duration_days' => 1]);

        // Cek filter pending
        $response = $this->actingAs($user)->get(route('booking.index', ['status' => 'pending']));
        $response->assertSee($bookingPending->code);
        $response->assertDontSee($bookingPaid->code);

        // Cek filter invalid
        $responseInvalid = $this->actingAs($user)->get(route('booking.index', ['status' => 'sampah']));
        $responseInvalid->assertSee($bookingPending->code);
        $responseInvalid->assertSee($bookingPaid->code);
    }

    public function test_3_batal_pending_sukses_dan_mencatat_history()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => BookingStatus::Pending]);

        $response = $this->actingAs($user)->post(route('booking.cancel', $booking->code));

        $response->assertRedirect(route('booking.index'));
        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals(BookingStatus::Cancelled, $booking->status);

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'to_status' => 'cancelled',
            'note' => 'Dibatalkan penyewa',
            'changed_by' => $user->id,
        ]);
    }

    public function test_4_batal_paid_ditolak_403()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => BookingStatus::Paid]);

        $response = $this->actingAs($user)->post(route('booking.cancel', $booking->code));
        $response->assertForbidden();

        $this->assertEquals(BookingStatus::Paid, $booking->fresh()->status);
    }

    public function test_5_batal_non_pemilik_ditolak_403()
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $owner->id, 'status' => BookingStatus::Pending]);

        $response = $this->actingAs($attacker)->post(route('booking.cancel', $booking->code));
        $response->assertForbidden();
    }

    public function test_6_show_detail_pesanan_hanya_untuk_pemilik()
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $owner->id, 'start_at' => now(), 'duration_days' => 1]);

        $this->actingAs($owner)->get(route('booking.show', $booking->code))
            ->assertOk()
            ->assertSee($booking->code);

        $this->actingAs($attacker)->get(route('booking.show', $booking->code))
            ->assertForbidden();
    }

    public function test_7_empty_state_tampil_jika_tidak_ada_pesanan()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('booking.index'));

        $response->assertOk();
        $response->assertSee('Belum ada pesanan');
        $response->assertSee('Pilih Kendaraan');
    }

    public function test_8_pagination_bekerja_dengan_benar()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        Booking::factory(11)->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now(),
            'duration_days' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('booking.index'));

        $bookings = $response->viewData('bookings');
        $this->assertCount(10, $bookings);
        $this->assertEquals(11, $bookings->total());
    }

    public function test_9_guest_redirected_to_login()
    {
        $this->get(route('booking.index'))->assertRedirect(route('login'));
    }
}
