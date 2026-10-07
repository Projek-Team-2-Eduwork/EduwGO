<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KendaraanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);
    }

    private function getAdmin()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_admin_bisa_mengakses_semua_halaman_kendaraan()
    {
        $admin = $this->getAdmin();
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($admin)->get(route('admin.kendaraan.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.kendaraan.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.kendaraan.edit', $vehicle->id))->assertOk();
        $this->actingAs($admin)->get(route('admin.kendaraan.show', $vehicle->id))->assertOk();
    }

    public function test_admin_bisa_tambah_kendaraan_dan_foto_ter_resize()
    {
        Storage::fake('public');
        $admin = $this->getAdmin();
        $type = VehicleType::factory()->create();
        $image = UploadedFile::fake()->image('motor.jpg', 2000, 2000); // Sengaja over-size > 1200

        $response = $this->actingAs($admin)->post(route('admin.kendaraan.store'), [
            'name' => 'Vario 150',
            'brand' => 'Honda',
            'vehicle_type_id' => $type->id,
            'plate_number' => 'B 1234 XY',
            'price_per_day' => 150000,
            'is_active' => true,
            'image' => $image,
        ]);

        $response->assertRedirect(route('admin.kendaraan.index'));
        $this->assertDatabaseHas('vehicles', ['name' => 'Vario 150', 'plate_number' => 'B 1234 XY']);

        $vehicle = Vehicle::first();
        $this->assertEquals('vario-150-b-1234-xy', $vehicle->slug);

        $path = str_replace('storage/', '', $vehicle->image);
        Storage::disk('public')->assertExists($path);

        // Assert ukuran maksimum image resize
        $size = getimagesize(Storage::disk('public')->path($path));
        $this->assertLessThanOrEqual(1200, $size[0]);
        $this->assertLessThanOrEqual(1200, $size[1]);
    }

    public function test_admin_bisa_update_kendaraan_dan_hapus_foto_lama()
    {
        Storage::fake('public');
        $admin = $this->getAdmin();
        $vehicle = Vehicle::factory()->create(['image' => 'storage/vehicles/old_image.jpg']);
        Storage::disk('public')->put('vehicles/old_image.jpg', 'dummy_content');

        $newImage = UploadedFile::fake()->image('new.jpg');

        $this->actingAs($admin)->put(route('admin.kendaraan.update', $vehicle->id), [
            'name' => 'NMAX',
            'vehicle_type_id' => $vehicle->vehicle_type_id,
            'plate_number' => $vehicle->plate_number,
            'price_per_day' => 200000,
            'image' => $newImage,
        ])->assertRedirect();

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'name' => 'NMAX']);

        $newPath = str_replace('storage/', '', $vehicle->fresh()->image);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing('vehicles/old_image.jpg'); // File lama terhapus
    }

    public function test_validasi_plate_number_duplikat_ditolak()
    {
        $admin = $this->getAdmin();
        Vehicle::factory()->create(['plate_number' => 'B 99 99 OK']);
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.kendaraan.update', $vehicle->id), [
            'name' => 'Testing',
            'vehicle_type_id' => $vehicle->vehicle_type_id,
            'plate_number' => 'b 99 99 ok ', // Akan disanitasi jadi B9999OK
            'price_per_day' => 100000,
        ]);

        $response->assertSessionHasErrors('plate_number');
    }

    public function test_non_admin_dan_guest_ditolak()
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        // Tamu dulu — actingAs membuat sesi login menempel untuk request berikutnya.
        $this->get(route('admin.kendaraan.index'))->assertRedirect();
        $this->actingAs($user)->get(route('admin.kendaraan.index'))->assertForbidden();
    }

    public function test_delete_dengan_booking_berakibat_soft_delete()
    {
        $admin = $this->getAdmin();
        $vehicle = Vehicle::factory()->create();
        Booking::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->actingAs($admin)->delete(route('admin.kendaraan.destroy', $vehicle->id));

        $this->assertNull(Vehicle::find($vehicle->id));
        $this->assertNotNull(Vehicle::withTrashed()->find($vehicle->id));
    }

    public function test_delete_tanpa_booking_berakibat_force_delete_dan_hapus_foto()
    {
        Storage::fake('public');
        $admin = $this->getAdmin();
        $vehicle = Vehicle::factory()->create(['image' => 'storage/vehicles/delete_me.jpg']);
        Storage::disk('public')->put('vehicles/delete_me.jpg', 'dummy');

        $this->actingAs($admin)->delete(route('admin.kendaraan.destroy', $vehicle->id));

        $this->assertNull(Vehicle::withTrashed()->find($vehicle->id));
        Storage::disk('public')->assertMissing('vehicles/delete_me.jpg');
    }

    public function test_filter_dan_badge_status_berfungsi_benar()
    {
        $admin = $this->getAdmin();

        $nonaktif = Vehicle::factory()->create(['is_active' => false]);
        $tersedia = Vehicle::factory()->create(['is_active' => true]);

        $disewa = Vehicle::factory()->create(['is_active' => true]);
        Booking::factory()->create([
            'vehicle_id' => $disewa->id,
            'status' => 'rented',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);

        // Cek Badge di Halaman Index
        $response = $this->actingAs($admin)->get(route('admin.kendaraan.index'));
        $response->assertSee('Nonaktif');
        $response->assertSee('Tersedia');
        $response->assertSee('Disewa');

        // Test Filter Disewa
        $resFilter = $this->actingAs($admin)->get(route('admin.kendaraan.index', ['status' => 'disewa']));
        $resFilter->assertSee($disewa->name);
        $resFilter->assertDontSee($nonaktif->name);
        $resFilter->assertDontSee($tersedia->name);
    }
}
