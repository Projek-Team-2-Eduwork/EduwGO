<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KendaraanJadwalTest extends TestCase
{
    use RefreshDatabase;

    public function test_jadwal_mengembalikan_rentang_per_hari_termasuk_buffer(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        $month = Carbon::now()->addMonth()->startOfMonth();

        // 10 10:00 → 11 14:00, buffer default 60 menit: 09:00 s.d. 15:00.
        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Paid,
            'start_at' => $month->copy()->addDays(9)->setTime(10, 0),
            'end_at' => $month->copy()->addDays(10)->setTime(14, 0),
        ]);

        // Booking batal tidak boleh muncul.
        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Cancelled,
            'start_at' => $month->copy()->addDays(19)->setTime(10, 0),
            'end_at' => $month->copy()->addDays(20)->setTime(10, 0),
        ]);

        $response = $this->getJson(route('kendaraan.jadwal', $vehicle).'?month='.$month->format('Y-m'));

        $response->assertOk()
            ->assertJsonPath('month', $month->format('Y-m'))
            ->assertJsonPath('buffer_minutes', 60)
            ->assertJsonPath('days.'.$month->copy()->addDays(9)->toDateString(), ['09:00–24:00'])
            ->assertJsonPath('days.'.$month->copy()->addDays(10)->toDateString(), ['00:00–15:00']);

        $this->assertCount(2, $response->json('days'));
    }

    public function test_jadwal_kosong_bila_tidak_ada_booking_dan_bulan_default_bulan_ini(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        $this->getJson(route('kendaraan.jadwal', $vehicle))
            ->assertOk()
            ->assertJsonPath('month', now()->format('Y-m'))
            ->assertExactJson(['month' => now()->format('Y-m'), 'buffer_minutes' => 60, 'days' => []]);
    }

    public function test_jadwal_tolak_format_bulan_tidak_valid(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        $this->getJson(route('kendaraan.jadwal', $vehicle).'?month=oktober')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['month']);
    }

    public function test_jadwal_unit_nonaktif_404(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => false]);

        $this->getJson(route('kendaraan.jadwal', $vehicle))->assertNotFound();
    }

    public function test_detail_motor_menampilkan_kalender(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        $this->get(route('kendaraan.detail', $vehicle))
            ->assertOk()
            ->assertSee('Ketersediaan Unit')
            ->assertSee(route('kendaraan.jadwal', $vehicle), false);
    }
}
