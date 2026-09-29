<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_tampil_dengan_default_bila_settings_kosong(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Rental Motor Cepat &amp; Aman, Mulai Rp75.000/hari', false)
            ->assertSee('Mulai Perjalanan Anda dalam 3 Langkah')
            ->assertSee('Syarat &amp; Ketentuan Rental Motor', false)
            ->assertSee('minimal 1x24 jam', false)
            ->assertSee(url('/kendaraan'));
    }

    public function test_katalog_ringkas_maksimal_8_motor_aktif_dengan_badge_status(): void
    {
        $type = VehicleType::factory()->create();
        Vehicle::factory()->count(9)->create(['vehicle_type_id' => $type->id, 'is_active' => true]);
        $nonaktif = Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'is_active' => false, 'name' => 'Motor Nonaktif']);

        $response = $this->get('/')->assertOk()->assertDontSee($nonaktif->name);

        $this->assertCount(8, $response->viewData('vehicles'));
        $response->assertSee('Tersedia');
        $response->assertSee('Lihat semua');
    }

    public function test_motor_dengan_booking_aktif_sekarang_berbadge_disewa(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Rented,
            'start_at' => now()->subHour(),
            'end_at' => now()->addDay(),
        ]);

        $this->get('/')->assertOk()->assertSee('Disewa')->assertDontSee('Tersedia');
    }

    public function test_tanpa_motor_menampilkan_empty_state(): void
    {
        $this->get('/')->assertOk()->assertSee('Belum ada motor tersedia');
    }

    public function test_jumlah_query_katalog_tidak_bertambah_per_motor(): void
    {
        Vehicle::factory()->count(2)->create();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/')->assertOk();
        $kecil = count(DB::getQueryLog());

        Vehicle::factory()->count(6)->create();
        DB::flushQueryLog();
        $this->get('/')->assertOk();

        $this->assertSame($kecil, count(DB::getQueryLog()));
    }
}
