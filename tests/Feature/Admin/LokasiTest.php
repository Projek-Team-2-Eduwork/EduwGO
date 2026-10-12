<?php

namespace Tests\Feature\Admin;

use App\Models\Location;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\LokasiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LokasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_tampil_untuk_admin()
    {
        $this->actingAs($this->actingAsAdmin())->get(route('admin.lokasi.index'))->assertOk()->assertSee('Kelola Lokasi');
    }

    public function test_create_lokasi_berhasil_dan_slug_otomatis()
    {
        $response = $this->actingAs($this->actingAsAdmin())->post(route('admin.lokasi.store'), [
            'name' => 'Cabang Malang Kota',
            'address' => 'Jl. Ijen No. 1, Malang',
            'phone' => '08111111111',
            'operating_hours' => 'Setiap hari, 08.00 – 20.00 WIB',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('locations', ['name' => 'Cabang Malang Kota', 'slug' => 'cabang-malang-kota']);
    }

    public function test_update_nama_berhasil_tapi_slug_tetap()
    {
        $lokasi = Location::factory()->create(['name' => 'Cabang Utama', 'slug' => 'cabang-utama']);

        $this->actingAs($this->actingAsAdmin())
            ->put(route('admin.lokasi.update', $lokasi->id), ['name' => 'Cabang Baru']);

        $this->assertDatabaseHas('locations', ['id' => $lokasi->id, 'name' => 'Cabang Baru', 'slug' => 'cabang-utama']);
    }

    public function test_slug_duplikat_ditolak()
    {
        Location::factory()->create(['name' => 'Lokasi Utama', 'slug' => 'lokasi-utama']);

        $response = $this->actingAs($this->actingAsAdmin())->post(route('admin.lokasi.store'), ['name' => 'Lokasi Utama']);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Location::count());
    }

    public function test_validasi_nama_kosong_ditolak()
    {
        $response = $this->actingAs($this->actingAsAdmin())->post(route('admin.lokasi.store'), ['name' => '']);
        $response->assertSessionHasErrors('name');
    }

    public function test_hapus_lokasi_tanpa_kendaraan_berhasil()
    {
        $lokasi = Location::factory()->create();
        $this->actingAs($this->actingAsAdmin())->delete(route('admin.lokasi.destroy', $lokasi->id));
        $this->assertDatabaseMissing('locations', ['id' => $lokasi->id]);
    }

    public function test_hapus_lokasi_dengan_kendaraan_ditolak()
    {
        $lokasi = Location::factory()->create();
        Vehicle::factory()->create(['location_id' => $lokasi->id]);

        $response = $this->actingAs($this->actingAsAdmin())->delete(route('admin.lokasi.destroy', $lokasi->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('locations', ['id' => $lokasi->id]);
    }

    public function test_non_admin_dan_guest_ditolak()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.lokasi.index'))->assertForbidden();
        auth()->logout();
        $this->get(route('admin.lokasi.index'))->assertRedirect(route('login'));
    }

    public function test_seeder_membuat_lokasi_dan_backfill_unit_tanpa_lokasi()
    {
        $vehicle = Vehicle::factory()->create(['location_id' => null]);

        $this->seed(LokasiSeeder::class);

        $lokasi = Location::where('slug', 'lokasi-utama')->first();
        $this->assertNotNull($lokasi);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'location_id' => $lokasi->id]);
    }
}
