<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KendaraanDurasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_durasi_mengembalikan_json_1_sampai_max_days(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        // Booking memblokir hari ke-3
        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Paid,
            'start_at' => '2026-10-12 12:00:00',
            'end_at' => '2026-10-12 15:00:00',
        ]);

        $response = $this->getJson('/api/kendaraan/'.$vehicle->id.'/durasi?start=2026-10-10T10:00');

        $response->assertOk();

        $data = $response->json();

        // Opsi 1..maxDays (default 5); key JSON numerik ter-decode jadi int oleh PHP
        $this->assertSame([1, 2, 3, 4, 5], array_keys($data));
        $this->assertTrue($data['1']['available']);
        $this->assertTrue($data['2']['available']);
        $this->assertFalse($data['3']['available']);
        $this->assertNotEmpty($data['1']['end_at']);
    }

    public function test_endpoint_tolak_start_masa_lalu(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->getJson('/api/kendaraan/'.$vehicle->id.'/durasi?start=2020-01-01T10:00')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start']);
    }

    public function test_endpoint_wajib_isi_start(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->getJson('/api/kendaraan/'.$vehicle->id.'/durasi')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start']);
    }
}
