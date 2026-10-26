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

        $this->get(route('kendaraan.detail', $vehicle))->assertOk()->assertSee('Sedang disewa');
    }

    public function test_user_login_melihat_modal_durasi(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('kendaraan.detail', $vehicle))
            ->assertOk()
            ->assertSee('Pilih tanggal sewa')
            ->assertSee('Lanjut ke checkout');
    }

    public function test_kalender_mobile_memiliki_bulan_vertikal_dan_footer_simpan(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('kendaraan.detail', $vehicle))
            ->assertOk()
            ->assertSee('booking-calendar-sheet', false)
            ->assertSee('calendarMonth in mobileMonths', false)
            ->assertSee('data-calendar-footer', false)
            ->assertSee('Hapus tanggal')
            ->assertSee('Simpan')
            ->assertSee('x-bind:disabled="!validRange"', false)
            ->assertSee('panel-away', false);
    }

    public function test_guest_klik_booking_menuju_login_lalu_kembali_ke_detail(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->get(route('kendaraan.detail', $vehicle))
            ->assertOk()
            ->assertSee(route('login'))
            ->assertSee('Masuk untuk pilih jadwal')
            ->assertDontSee('Lanjut ke checkout');

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
        $response->assertSee('Motor serupa di cabang ini')->assertDontSee($inactive->name)->assertDontSee($differentType->name);
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

    public function test_detail_menampilkan_cabang_kartu_pesan_dan_kembali_ke_hasil(): void
    {
        $vehicle = Vehicle::factory()->create(['price_per_day' => 70000]);
        $branch = $vehicle->branch->name;

        $this->get('/kendaraan?harga=termurah&q=a')->assertOk();

        $this->get(route('kendaraan.detail', $vehicle))
            ->assertOk()
            ->assertSee('Ambil di '.$branch)
            ->assertSee('Lokasi pengambilan')
            ->assertSee('Rp70.000')
            ->assertDontSee('Kembali ke hasil')
            ->assertSee('<a href="'.e(url('/kendaraan?harga=termurah&q=a')).'" class="inline-flex min-h-11 shrink-0 items-center gap-1 hover:underline">', false)
            ->assertSee('Baca seluruh ketentuan')
            ->assertSeeInOrder(['<h1', 'Ambil di '.$branch, 'Foto '.$vehicle->name], false);
    }

    public function test_rekomendasi_tidak_menampilkan_plat(): void
    {
        $type = VehicleType::factory()->create();
        $vehicle = Vehicle::factory()->create(['vehicle_type_id' => $type->id]);
        $same = Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'plate_number' => 'N 9999 ZZ']);

        $this->get(route('kendaraan.detail', $vehicle))
            ->assertOk()
            ->assertSee($same->name)
            ->assertDontSee('N 9999 ZZ');
    }
}
