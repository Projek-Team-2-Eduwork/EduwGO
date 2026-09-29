<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingUlangTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response([
                'id' => 'inv_dummy_new_rebook',
                'invoice_url' => 'https://checkout.xendit.co/web/inv_dummy_new',
                'expiry_date' => now()->addHour()->toISOString(),
            ], 200),
        ]);
    }

    public function test_ulang_dari_booking_expired_mengisi_session_dan_redirect()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $start = now()->addDays(3)->setTime(10, 0);

        // Buat booking manual untuk menghindari random EG code dari factory jika ada
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Expired,
            'start_at' => $start,
            'duration_days' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('booking.rebook', $booking->code));

        $response->assertRedirect(route('kendaraan.detail', ['vehicle' => $booking->vehicle, 'ulang' => 1]));
        $response->assertSessionHas('booking.start_at', $start->format('Y-m-d\TH:i'));
        $response->assertSessionHas('booking.days', 2);
    }

    public function test_ulang_start_at_lewat_dibulatkan_ke_jam_berikutnya()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Expired,
            'start_at' => now()->subDay(),
            'duration_days' => 3,
        ]);

        $response = $this->actingAs($user)->get(route('booking.rebook', $booking->code));

        $expectedStart = now()->startOfHour()->addHour()->format('Y-m-d\TH:i');
        $response->assertSessionHas('booking.start_at', $expectedStart);
    }

    public function test_booking_baru_dari_hasil_ulang_membuat_invoice_baru()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['is_active' => true, 'price_per_day' => 100000]);

        $oldBooking = Booking::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Expired,
            'start_at' => now()->subDay(),
            'duration_days' => 2,
        ]);
        Payment::factory()->create(['booking_id' => $oldBooking->id, 'status' => 'expired']);

        $startFormatted = now()->startOfHour()->addHour()->format('Y-m-d\TH:i');

        // User tiba di checkout (berasal dari fetch API -> click proceed di frontend)
        $response = $this->actingAs($user)->post(route('checkout.store', $vehicle->id), [
            'start' => $startFormatted,
            'days' => 2,
            'customer_name' => 'Budi Rebook',
            'customer_phone' => '08123456789',
        ]);

        $this->assertSame(2, Booking::count(), 'Harus ada 2 booking');

        $oldBooking->refresh();
        $this->assertEquals(BookingStatus::Expired, $oldBooking->status, 'Booking lama tetap expired');

        $newBooking = Booking::where('id', '!=', $oldBooking->id)->first();
        $this->assertNotEquals($oldBooking->code, $newBooking->code);
        $this->assertMatchesRegularExpression('/^EG\.\d{6}$/', $newBooking->code);
        $this->assertEquals(BookingStatus::Pending, $newBooking->status);

        $this->assertSame(2, Payment::count());
        $this->assertEquals('pending', $newBooking->latestPayment->status, 'Membuat payment/invoice baru');
    }

    public function test_ulang_ditolak_bukan_pemilik()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Expired,
        ]);

        $response = $this->actingAs($otherUser)->get(route('booking.rebook', $booking->code));

        $response->assertForbidden();
    }
}
