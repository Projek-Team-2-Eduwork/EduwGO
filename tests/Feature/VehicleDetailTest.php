<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_tampil_untuk_slug_valid(): void
    {
        $vehicle = Vehicle::factory()->create(['tank_capacity' => 7]);

        $this->get(route('kendaraan.detail', $vehicle))
            ->assertOk()
            ->assertSee($vehicle->name)
            ->assertSee($vehicle->brand)
            ->assertSee($vehicle->plate_number)
            ->assertSee('7 liter')
            ->assertSee('Tersedia')
            ->assertSee('<title>'.$vehicle->name.' — EduwGo</title>', false);
    }

    public function test_detail_menampilkan_disewa_bila_ada_booking_aktif_sekarang(): void
    {
        $vehicle = Vehicle::factory()->create();
        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Rented,
            'start_at' => now()->subHour(),
            'end_at' => now()->addDay(),
        ]);

        $this->get(route('kendaraan.detail', $vehicle))->assertOk()->assertSee('Disewa');
    }

    public function test_user_login_melihat_modal_durasi(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('kendaraan.detail', $vehicle))
            ->assertOk()
            ->assertSee('Pilih Durasi Sewa');
    }

    public function test_guest_klik_booking_menuju_login_lalu_kembali_ke_detail(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->get(route('kendaraan.detail', $vehicle))
            ->assertOk()
            ->assertSee(route('login'))
            ->assertDontSee('Pilih Durasi Sewa');

        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('kendaraan.detail', $vehicle));
    }

    public function test_detail_404_untuk_motor_nonaktif_dan_soft_deleted(): void
    {
        $inactive = Vehicle::factory()->create(['is_active' => false]);
        $deleted = Vehicle::factory()->create();
        $deleted->delete();

        $this->get(route('kendaraan.detail', $inactive))->assertNotFound();
        $this->get('/kendaraan/'.$deleted->slug)->assertNotFound();
        $this->get('/kendaraan/slug-tidak-ada')->assertNotFound();
    }

    public function test_rekomendasi_tipe_sama_aktif_dan_tidak_memuat_motor_ini(): void
    {
        $type = VehicleType::factory()->create(['name' => 'Matic', 'slug' => 'matic']);
        $other = VehicleType::factory()->create(['name' => 'Cub', 'slug' => 'cub']);
        $vehicle = Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'name' => 'Utama Detail']);
        $same = Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'name' => 'Saudara Matic']);
        $inactive = Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'name' => 'Matic Nonaktif', 'is_active' => false]);
        $differentType = Vehicle::factory()->create(['vehicle_type_id' => $other->id, 'name' => 'Beda Cub']);

        $response = $this->get(route('kendaraan.detail', $vehicle))->assertOk();

        $recommendations = $response->viewData('recommendations');
        $this->assertSame([$same->id], $recommendations->pluck('id')->all());
        $response->assertSee('Rekomendasi Kendaraan')->assertDontSee($inactive->name)->assertDontSee($differentType->name);
    }

    public function test_rekomendasi_mengikuti_filter_session_dan_maksimal_8(): void
    {
        $type = VehicleType::factory()->create(['name' => 'Matic', 'slug' => 'matic']);
        $vehicle = Vehicle::factory()->create(['vehicle_type_id' => $type->id]);
        $booked = Vehicle::factory()->create(['vehicle_type_id' => $type->id]);
        // Nama factory hanya 10 varian unik, jadi sisa unit digandakan dari $vehicle.
        foreach (range(1, 9) as $i) {
            $vehicle->replicate()->fill([
                'name' => "Motor Rekomendasi {$i}",
                'slug' => "motor-rekomendasi-{$i}",
                'plate_number' => "B 100{$i} XX",
            ])->save();
        }

        $start = now()->addDays(3)->startOfHour();
        Booking::factory()->create([
            'vehicle_id' => $booked->id,
            'status' => BookingStatus::Paid,
            'start_at' => $start,
            'end_at' => $start->copy()->addDays(2),
        ]);

        $response = $this->withSession(['booking.filter' => [
            'start_at' => $start->toIso8601String(),
            'days' => 2,
            'type' => $type->id,
        ]])->get(route('kendaraan.detail', $vehicle))->assertOk();

        $ids = $response->viewData('recommendations')->pluck('id');
        $this->assertCount(8, $ids);
        $this->assertNotContains($vehicle->id, $ids);
        $this->assertNotContains($booked->id, $ids);
    }

    public function test_filter_session_tidak_valid_diabaikan(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->withSession(['booking.filter' => ['start_at' => 'bukan-tanggal', 'days' => 99]])
            ->get(route('kendaraan.detail', $vehicle))
            ->assertOk();
    }
}
