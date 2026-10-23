<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\XenditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // MOCK XenditService sesuai aturan EG-29
        $this->mock(XenditService::class, function ($mock) {
            $mock->shouldReceive('createInvoice')->andReturnUsing(function ($booking) {
                return Payment::factory()->create([
                    'booking_id' => $booking->id,
                    'status' => 'pending',
                    'gateway_reference' => 'inv_mock_123',
                ]);
            });
        });
    }

    public function test_checkout_sukses_tanpa_blacklist()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post(route('checkout.store', $vehicle->id), [
            'start' => now()->addDays(2)->toISOString(),
            'days' => 2,
            'customer_name' => 'Budi',
            'customer_phone' => '08123',
        ]);

        $booking = Booking::first();
        $this->assertNotNull($booking);
        $this->assertMatchesRegularExpression('/^EG\.\d{6}$/', $booking->code);
        $response->assertRedirect(route('booking.waiting', $booking->code));
        $this->assertEquals(BookingStatus::Pending, $booking->status);
    }

    public function test_checkout_bentrok_ditolak()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        $start = now()->addDays(2);

        Booking::factory()->paid()->create([
            'vehicle_id' => $vehicle->id,
            'start_at' => $start->copy()->subHours(2),
            'end_at' => $start->copy()->addDays(2),
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store', $vehicle->id), [
            'start' => $start->toISOString(),
            'days' => 2,
            'customer_name' => 'Andi',
            'customer_phone' => '08111',
        ]);

        $response->assertSessionHasErrors('start');
        $this->assertSame(1, Booking::count()); // Hanya satu dari setup awal
    }

    public function test_checkout_user_unverified_ditolak()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($user)->post(route('checkout.store', $vehicle->id), [
            'start' => now()->addDay()->toISOString(),
            'days' => 1,
        ]);

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_race_condition_double_submit_hanya_satu_berhasil()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        $start = now()->addDays(3)->toISOString();

        $response1 = $this->actingAs($user1)->post(route('checkout.store', $vehicle->id), [
            'start' => $start, 'days' => 2, 'customer_name' => 'A', 'customer_phone' => '1',
        ]);
        $response1->assertRedirect();

        $response2 = $this->actingAs($user2)->post(route('checkout.store', $vehicle->id), [
            'start' => $start, 'days' => 2, 'customer_name' => 'B', 'customer_phone' => '2',
        ]);
        $response2->assertSessionHasErrors('start');

        $this->assertSame(1, Booking::count());
    }

    public function test_halaman_detail_dan_checkout_tampil()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        // Detail memakai slug (EG-25); modal durasi hanya dirender untuk user login.
        $responseDetail = $this->actingAs($user)->get(route('kendaraan.detail', $vehicle));
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
