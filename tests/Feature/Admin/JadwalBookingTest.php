<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JadwalBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup base seeder untuk settings dsb jika dibutuhkan aplikasimu
        // $this->seed(\Database\Seeders\SettingSeeder::class);
    }

    private function getAdmin()
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_halaman_menampilkan_booking_aktif_unit()
    {
        $admin = $this->getAdmin();
        $vehicle = Vehicle::factory()->create();

        $booking = Booking::factory()->paid()->create([
            'vehicle_id' => $vehicle->id,
            'customer_name' => 'Budi Santoso',
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.kendaraan.show', $vehicle));

        $response->assertOk();
        $response->assertSee('Jadwal Booking');
        $response->assertSee($booking->code);
        $response->assertSee('Budi Santoso');
        $response->assertSee('buffer');
    }

    public function test_tidak_menampilkan_cancelled_dan_expired()
    {
        $admin = $this->getAdmin();
        $vehicle = Vehicle::factory()->create();

        Booking::factory()->cancelled()->create([
            'vehicle_id' => $vehicle->id,
            'code' => 'EG.900001',
            'start_at' => now()->addDays(1),
            'end_at' => now()->addDays(2),
        ]);

        Booking::factory()->expired()->create([
            'vehicle_id' => $vehicle->id,
            'code' => 'EG.900002',
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(4),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.kendaraan.show', $vehicle));

        $response->assertOk();
        $response->assertDontSee('EG.900001');
        $response->assertDontSee('EG.900002');
    }
}
