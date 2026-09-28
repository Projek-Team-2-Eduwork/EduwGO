<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response([
                'id' => 'inv_12345',
                'invoice_url' => 'https://checkout.xendit.co/web/inv_12345',
                'expiry_date' => now()->addHour()->toISOString(),
            ], 200),
        ]);
    }

    public function test_checkout_sukses_redirect_ke_waiting_dan_simpan_db()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'phone' => '08123456789']);
        $vehicle = Vehicle::factory()->create(['is_active' => true, 'price_per_day' => 100000]);
        $start = now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);

        $response = $this->actingAs($user)->post(route('checkout.store', $vehicle->id), [
            'start' => $start->toISOString(),
            'days' => 2,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
        ]);

        $booking = Booking::first();
        $this->assertNotNull($booking);
        $this->assertMatchesRegularExpression('/^EG\.\d{6}$/', $booking->code);
        $response->assertRedirect(route('booking.waiting', $booking->code));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::Pending->value,
            'price_per_day' => 100000,
            'total_amount' => 200000,
            'duration_days' => 2,
        ]);

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'to_status' => 'pending',
        ]);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'status' => 'pending',
            'gateway_reference' => 'inv_12345',
        ]);
    }

    public function test_checkout_bentrok_mengembalikan_error_start()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        $start = now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);

        // Buat booking dummy yang overlapping
        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Paid,
            'start_at' => $start->copy()->subHours(2),
            'end_at' => $start->copy()->addDays(2),
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store', $vehicle->id), [
            'start' => $start->toISOString(),
            'days' => 2,
            'customer_name' => 'Budi',
            'customer_phone' => '08123',
        ]);

        $response->assertSessionHasErrors('start');
        $this->assertSame(1, Booking::count()); // Hanya satu dari setup awal
    }

    public function test_email_belum_terverifikasi_redirect_ke_notice()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $vehicle = Vehicle::factory()->create();

        $responseGet = $this->actingAs($user)->get(route('checkout.show', ['vehicle' => $vehicle->id, 'start' => now()->addDay()->toISOString(), 'days' => 1]));
        $responseGet->assertRedirect(route('verification.notice'));

        $responsePost = $this->actingAs($user)->post(route('checkout.store', $vehicle->id), [
            'start' => now()->addDay()->toISOString(),
            'days' => 1,
            'customer_name' => 'Budi',
            'customer_phone' => '08123',
        ]);
        $responsePost->assertRedirect(route('verification.notice'));
    }

    public function test_dua_request_paralel_di_unit_terakhir_ditolak_salah_satu()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['is_active' => true, 'price_per_day' => 50000]);
        $start = now()->addDays(3)->setHour(10)->setMinute(0)->setSecond(0);

        $response1 = $this->actingAs($user1)->post(route('checkout.store', $vehicle->id), [
            'start' => $start->toISOString(),
            'days' => 2,
            'customer_name' => $user1->name,
            'customer_phone' => '08111',
        ]);
        $response1->assertRedirect(route('booking.waiting', Booking::first()->code));

        $response2 = $this->actingAs($user2)->post(route('checkout.store', $vehicle->id), [
            'start' => $start->toISOString(),
            'days' => 2,
            'customer_name' => $user2->name,
            'customer_phone' => '08222',
        ]);
        $response2->assertSessionHasErrors('start');

        $this->assertSame(1, Booking::count()); // Hanya 1 yang berhasil
    }

    public function test_halaman_detail_dan_checkout_tampil()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $responseDetail = $this->get(route('kendaraan.detail', $vehicle->id));
        $responseDetail->assertOk();
        $responseDetail->assertSee('Pilih Durasi Sewa');

        $start = now()->addDays(2)->toISOString();
        $responseCheckout = $this->actingAs($user)->get(route('checkout.show', [
            'vehicle' => $vehicle->id,
            'start' => $start,
            'days' => 3,
        ]));
        $responseCheckout->assertOk();
        $responseCheckout->assertSee($vehicle->name);
    }
}
