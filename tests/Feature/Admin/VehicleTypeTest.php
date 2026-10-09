<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_halaman_tampil_untuk_admin()
    {
        $this->actingAs($this->actingAsAdmin())->get(route('admin.tipe-kendaraan.index'))->assertOk()->assertSee('Kelola Tipe Kendaraan');
    }

    public function test_create_tipe_berhasil_dan_slug_otomatis()
    {
        $response = $this->actingAs($this->actingAsAdmin())->post(route('admin.tipe-kendaraan.store'), ['name' => 'Bebek']);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('vehicle_types', ['name' => 'Bebek', 'slug' => 'bebek']);
    }

    public function test_update_nama_berhasil_tapi_slug_tetap()
    {
        $type = VehicleType::factory()->create(['name' => 'Matic', 'slug' => 'matic']);
        $this->actingAs($this->actingAsAdmin())->put(route('admin.tipe-kendaraan.update', $type->id), ['name' => 'Skuter Matic']);
        $this->assertDatabaseHas('vehicle_types', ['id' => $type->id, 'name' => 'Skuter Matic', 'slug' => 'matic']); // Slug tidak terganti
    }

    public function test_hapus_tipe_tanpa_kendaraan_berhasil()
    {
        $type = VehicleType::factory()->create();
        $this->actingAs($this->actingAsAdmin())->delete(route('admin.tipe-kendaraan.destroy', $type->id));
        $this->assertDatabaseMissing('vehicle_types', ['id' => $type->id]);
    }

    public function test_hapus_tipe_dengan_kendaraan_ditolak()
    {
        $type = VehicleType::factory()->create();
        Vehicle::factory()->create(['vehicle_type_id' => $type->id]);
        $response = $this->actingAs($this->actingAsAdmin())->delete(route('admin.tipe-kendaraan.destroy', $type->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('vehicle_types', ['id' => $type->id]);
    }

    public function test_validasi_nama_kosong_ditolak()
    {
        $response = $this->actingAs($this->actingAsAdmin())->post(route('admin.tipe-kendaraan.store'), ['name' => '']);
        $response->assertSessionHasErrors('name');
    }

    public function test_non_admin_dan_guest_ditolak()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.tipe-kendaraan.index'))->assertForbidden();
        auth()->logout();
        $this->get(route('admin.tipe-kendaraan.index'))->assertRedirect(route('login'));
    }
}
