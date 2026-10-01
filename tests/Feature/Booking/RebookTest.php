<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\XenditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RebookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(XenditService::class, function ($mock) {
            $mock->shouldReceive('createInvoice')->andReturnUsing(function ($booking) {
                return Payment::factory()->create(['booking_id' => $booking->id, 'status' => 'pending']);
            });
        });
    }

    public function test_booking_baru_berhasil_setelah_booking_lama_dicancel_atau_expired()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        $start = now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);

        // Booking 1: Dibatalkan
        Booking::factory()->cancelled()->create([
            'vehicle_id' => $vehicle->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addDays(2),
        ]);

        // Booking 2: Expired
        Booking::factory()->expired()->create([
            'vehicle_id' => $vehicle->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addDays(2),
        ]);

        // Booking 3: User mencoba booking ulang di waktu yang sama
        $response = $this->actingAs($user)->post(route('checkout.store', $vehicle->id), [
            'start' => $start->toISOString(),
            'days' => 2,
            'customer_name' => 'Budi',
            'customer_phone' => '08123',
        ]);

        $response->assertRedirect();

        // Hitung yang pending saja
        $this->assertSame(1, Booking::where('status', 'pending')->count());
    }
}
