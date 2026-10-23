<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingResultPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_lunas_owner_lihat_halaman_berhasil()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Paid,
        ]);

        $response = $this->actingAs($user)->get(route('booking.success', $booking->code));
        $response->assertOk();
        $response->assertSee('Yeay! Pembayaran Berhasil');
    }

    public function test_booking_pending_owner_lihat_memproses()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Pending,
        ]);

        $response = $this->actingAs($user)->get(route('booking.success', $booking->code));
        $response->assertOk();
        $response->assertSee('Memproses pembayaran');
    }

    public function test_booking_expired_owner_diarahkan_ke_halaman_gagal()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Expired,
        ]);

        $response = $this->actingAs($user)->get(route('booking.success', $booking->code));
        $response->assertRedirect(route('booking.failed', $booking->code));
    }

    public function test_halaman_gagal_tampil_untuk_booking_expired()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Expired,
        ]);

        $response = $this->actingAs($user)->get(route('booking.failed', $booking->code));
        $response->assertOk();
        $response->assertSee('Ups, Waktunya Habis!');
        $response->assertSee(route('booking.rebook', $booking->code));
    }

    public function test_booking_lunas_owner_diarahkan_ke_halaman_berhasil_jika_akses_gagal()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Paid,
        ]);

        $response = $this->actingAs($user)->get(route('booking.failed', $booking->code));
        $response->assertRedirect(route('booking.success', $booking->code));
    }

    public function test_endpoint_status_polling_bekerja_benar()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Pending,
        ]);

        $response = $this->actingAs($user)->getJson(route('booking.status', $booking->code));
        $response->assertOk();
        $response->assertJson([
            'status' => 'pending',
            'paid' => false,
        ]);

        $booking->update(['status' => BookingStatus::Paid]);

        $response2 = $this->actingAs($user)->getJson(route('booking.status', $booking->code));
        $response2->assertOk();
        $response2->assertJson([
            'status' => 'paid',
            'paid' => true,
        ]);
    }

    public function test_akses_halaman_pesanan_oleh_bukan_pemilik_ditolak()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Pending,
        ]);

        $responseSuccess = $this->actingAs($otherUser)->get(route('booking.success', $booking->code));
        $responseSuccess->assertForbidden();

        $responseFailed = $this->actingAs($otherUser)->get(route('booking.failed', $booking->code));
        $responseFailed->assertForbidden();

        $responseStatus = $this->actingAs($otherUser)->getJson(route('booking.status', $booking->code));
        $responseStatus->assertForbidden();
    }
}
