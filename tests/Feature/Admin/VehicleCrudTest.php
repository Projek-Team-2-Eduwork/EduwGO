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

class VehicleCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user']);

        Storage::fake('public');
    }

    private function admin(): User
    {
        // Delegasi ke helper induk: membuat role admin bila belum ada + actingAs sesi admin.
        return $this->actingAsAdmin();
    }

    private function user(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        return $user;
    }

    private function tipeMatic(): VehicleType
    {
        return VehicleType::query()->firstOrCreate(
            ['slug' => 'matic'],
            ['name' => 'Matic'],
        );
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Honda Vario 160',
            'brand' => 'Honda',
            'vehicle_type_id' => $this->tipeMatic()->id,
            'tank_capacity' => 5.5,
            'plate_number' => 'b 1234 xyz',
            'price_per_day' => 100000,
            'image' => UploadedFile::fake()->image('vario.jpg', 1600, 1200),
            'description' => 'Unit uji coba.',
            'is_active' => '1',
        ], $override);
    }

    public function test_admin_dapat_membuka_seluruh_halaman_kendaraan(): void
    {
        $admin = $this->admin();
        $vehicle = Vehicle::factory()->create();

        $this->get(route('admin.kendaraan.index'))->assertOk();
        $this->get(route('admin.kendaraan.create'))->assertOk();
        $this->get(route('admin.kendaraan.show', $vehicle))->assertOk();
        $this->get(route('admin.kendaraan.edit', $vehicle))->assertOk();
    }

    public function test_admin_dapat_menambah_kendaraan_dengan_foto_terresize(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.kendaraan.store'), $this->payload());

        $vehicle = Vehicle::query()->where('name', 'Honda Vario 160')->firstOrFail();

        $response->assertRedirect(route('admin.kendaraan.index'));
        $this->assertSame('B 1234 XYZ', $vehicle->plate_number, 'Plat harus di-upper-case-kan');
        $this->assertSame('honda-vario-160-b-1234-xyz', $vehicle->slug, 'Slug otomatis dari nama + plat');
        $this->assertTrue((bool) $vehicle->is_active);

        $this->assertStringStartsWith('storage/vehicles/', $vehicle->image);
        $relative = str_replace('storage/', '', $vehicle->image);
        Storage::disk('public')->assertExists($relative);

        $size = getimagesize(Storage::disk('public')->path($relative));
        $this->assertLessThanOrEqual(1200, $size[0], 'Lebar hasil resize maks 1200px');
        $this->assertLessThanOrEqual(1200, $size[1], 'Tinggi hasil resize maks 1200px');
    }

    public function test_plat_nomor_duplikat_ditolak_sa_tambah(): void
    {
        Vehicle::factory()->create(['plate_number' => 'B 1111 AA']);
        $count = Vehicle::count();

        $this->actingAs($this->admin())
            ->post(route('admin.kendaraan.store'), $this->payload(['plate_number' => 'b 1111 aa']))
            ->assertSessionHasErrors('plate_number');

        $this->assertSame($count, Vehicle::count(), 'Data tidak boleh bertambah saat validasi gagal');
    }

    public function test_admin_dapat_update_dan_mematikan_status_aktif(): void
    {
        $admin = $this->admin();
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        // Form edit selalu mengirim is_active (hidden field value 0 saat checkbox mati).
        $this->put(route('admin.kendaraan.update', $vehicle), [
            'name' => 'Unit Diubah',
            'brand' => 'Honda',
            'vehicle_type_id' => $this->tipeMatic()->id,
            'tank_capacity' => 6,
            'plate_number' => $vehicle->plate_number,
            'price_per_day' => 150000,
            'description' => 'Diubah.',
            'is_active' => '0',
        ])->assertRedirect(route('admin.kendaraan.index'));

        $vehicle->refresh();
        $this->assertFalse((bool) $vehicle->is_active, 'Checkbox mati harus menonaktifkan unit');
        $this->assertSame('Unit Diubah', $vehicle->name);

        $this->put(route('admin.kendaraan.update', $vehicle), [
            'name' => 'Unit Diubah',
            'brand' => 'Honda',
            'vehicle_type_id' => $this->tipeMatic()->id,
            'tank_capacity' => 6,
            'plate_number' => $vehicle->plate_number,
            'price_per_day' => 150000,
            'description' => 'Diubah.',
            'is_active' => '1',
        ])->assertRedirect(route('admin.kendaraan.index'));

        $this->assertTrue((bool) $vehicle->refresh()->is_active, 'Checkbox hidup harus mengaktifkan kembali unit');
    }

    public function test_update_ditolak_jika_plat_milik_unit_lain(): void
    {
        $admin = $this->admin();
        $a = Vehicle::factory()->create(['plate_number' => 'B 2222 BB']);
        Vehicle::factory()->create(['plate_number' => 'B 3333 CC']);

        $this->put(route('admin.kendaraan.update', $a), [
            'name' => $a->name,
            'brand' => $a->brand,
            'vehicle_type_id' => $this->tipeMatic()->id,
            'plate_number' => 'b 3333 cc',
            'price_per_day' => 100000,
            'is_active' => '1',
        ])->assertSessionHasErrors('plate_number');

        $this->assertSame('B 2222 BB', $a->fresh()->plate_number, 'Plat tidak boleh berubah saat validasi gagal');
    }

    public function test_user_biasa_ditolak_403_dan_tamu_dialihkan_ke_login(): void
    {
        $vehicle = Vehicle::factory()->create();

        // Tamu dulu — actingAs membuat sesi login menempel untuk request berikutnya.
        $this->get(route('admin.kendaraan.index'))->assertRedirect(route('login'));

        $this->actingAs($this->user())->get(route('admin.kendaraan.index'))->assertForbidden();
        $this->actingAs($this->user())->get(route('admin.kendaraan.create'))->assertForbidden();
        $this->actingAs($this->user())->get(route('admin.kendaraan.edit', $vehicle))->assertForbidden();
        $this->actingAs($this->user())->post(route('admin.kendaraan.store'), $this->payload())->assertForbidden();
        $this->actingAs($this->user())->delete(route('admin.kendaraan.destroy', $vehicle))->assertForbidden();
    }

    public function test_hapus_unit_punya_booking_dilakukan_soft_delete(): void
    {
        $admin = $this->admin();
        $vehicle = Vehicle::factory()->create();
        Booking::factory()->for($vehicle)->create();

        $this->actingAs($admin)
            ->delete(route('admin.kendaraan.destroy', $vehicle))
            ->assertRedirect(route('admin.kendaraan.index'));

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
        $this->assertNotNull($vehicle->fresh());
    }

    public function test_hapus_unit_tanpa_booking_dilakukan_permanen_dengan_foto(): void
    {
        $admin = $this->admin();
        $vehicle = Vehicle::factory()->create(['image' => 'storage/vehicles/uji-hapus.jpg']);
        Storage::disk('public')->put('vehicles/uji-hapus.jpg', 'FILE-FOTO');

        $this->actingAs($admin)
            ->delete(route('admin.kendaraan.destroy', $vehicle))
            ->assertRedirect(route('admin.kendaraan.index'));

        $this->assertNull(Vehicle::withTrashed()->find($vehicle->id), 'Unit tanpa booking harus terhapus permanen');
        Storage::disk('public')->assertMissing('vehicles/uji-hapus.jpg', 'Foto ikut terhapus');
    }

    public function test_badge_status_tampil_benar_tanpa_menghitung_pending(): void
    {
        Vehicle::factory()->create(['name' => 'Unit Nonaktif', 'is_active' => false]);

        $disewa = Vehicle::factory()->create(['name' => 'Unit Disewa', 'is_active' => true]);
        Booking::factory()->paid()->for($disewa)->create();

        Vehicle::factory()->create(['name' => 'Unit Tersedia', 'is_active' => true]);

        $pending = Vehicle::factory()->create(['name' => 'Unit Pending', 'is_active' => true]);
        Booking::factory()->pending()->for($pending)->create();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.kendaraan.index'))
            ->assertOk()
            ->getContent();

        // Badge "Disewa" (merah) hanya untuk booking paid/rented — pending tidak dihitung.
        $this->assertSame(1, preg_match_all('/rounded bg-red-100[^>]*">\s*Disewa\s*</', $html));
        // Dua unit aktif tanpa booking berjalan → Tersedia.
        $this->assertSame(2, preg_match_all('/rounded bg-green-100[^>]*">\s*Tersedia\s*</', $html));
        $this->assertSame(1, preg_match_all('/rounded bg-gray-100[^>]*">\s*Nonaktif\s*</', $html));

        // Badge merah menempel pada kartu "Unit Disewa" (bukan kartu lain).
        $this->assertMatchesRegularExpression(
            '/rounded bg-red-100[^>]*">\s*Disewa\s*<\/span>.*?Unit Disewa/s',
            $html
        );
    }

    public function test_filter_status_tipe_dan_pencarian(): void
    {
        $admin = $this->admin();
        $matic = $this->tipeMatic();

        Vehicle::factory()->create(['name' => 'Unit Nonaktif', 'is_active' => false, 'plate_number' => 'B 1001 AA', 'vehicle_type_id' => $matic->id]);

        $disewa = Vehicle::factory()->create(['name' => 'Unit Disewa', 'is_active' => true, 'plate_number' => 'B 1002 BB', 'vehicle_type_id' => $matic->id]);
        Booking::factory()->paid()->for($disewa)->create();

        Vehicle::factory()->create(['name' => 'Unit Tersedia', 'is_active' => true, 'plate_number' => 'D 4321 ZZ', 'vehicle_type_id' => $matic->id]);

        // Unit nonaktif tapi sedang punya booking paid: harus dikeluarkan dari filter "disewa".
        $arsip = Vehicle::factory()->create(['name' => 'Unit Arsip', 'is_active' => false, 'plate_number' => 'B 1003 CC', 'vehicle_type_id' => $matic->id]);
        Booking::factory()->paid()->for($arsip)->create();

        $sport = VehicleType::query()->firstOrCreate(['slug' => 'sport'], ['name' => 'Sport']);
        Vehicle::factory()->create(['name' => 'Unit Sport', 'is_active' => true, 'plate_number' => 'B 1004 DD', 'vehicle_type_id' => $sport->id]);

        // Status: tersedia → hanya unit aktif tanpa booking berjalan.
        $this->get(route('admin.kendaraan.index', ['status' => 'tersedia']))
            ->assertOk()
            ->assertSee('Unit Tersedia')
            ->assertSee('Unit Sport')
            ->assertDontSee('Unit Disewa')
            ->assertDontSee('Unit Nonaktif')
            ->assertDontSee('Unit Arsip');

        // Status: disewa → hanya unit AKTIF yang sedang disewa.
        $this->get(route('admin.kendaraan.index', ['status' => 'disewa']))
            ->assertOk()
            ->assertSee('Unit Disewa')
            ->assertDontSee('Unit Arsip')
            ->assertDontSee('Unit Tersedia')
            ->assertDontSee('Unit Nonaktif');

        // Status: nonaktif.
        $this->get(route('admin.kendaraan.index', ['status' => 'nonaktif']))
            ->assertOk()
            ->assertSee('Unit Nonaktif')
            ->assertSee('Unit Arsip')
            ->assertDontSee('Unit Tersedia');

        // Filter tipe.
        $this->get(route('admin.kendaraan.index', ['tipe' => $sport->id]))
            ->assertOk()
            ->assertSee('Unit Sport')
            ->assertDontSee('Unit Tersedia')
            ->assertDontSee('Unit Disewa');

        // Pencarian nama.
        $this->get(route('admin.kendaraan.index', ['q' => 'Arsip']))
            ->assertOk()
            ->assertSee('Unit Arsip')
            ->assertDontSee('Unit Tersedia');

        // Pencarian plat.
        $this->get(route('admin.kendaraan.index', ['q' => '4321']))
            ->assertOk()
            ->assertSee('Unit Tersedia')
            ->assertDontSee('Unit Sport')
            ->assertDontSee('Unit Disewa');
    }

    public function test_slug_bentrok_dengan_unit_soft_deleted_tidak_menimbulkan_500(): void
    {
        $admin = $this->admin();

        $lama = Vehicle::factory()->create([
            'name' => 'Honda Vario 160',
            'plate_number' => 'B 1234 XYZ',
            'slug' => 'honda-vario-160-b-1234-xyz', // seperti hasil generateSlug admin
        ]);
        Booking::factory()->for($lama)->create();
        $lama->delete(); // soft delete: baris + slug lama masih menempel di DB

        // Slug baru = slug('Honda Vario') . '-' . slug('160 B 1234 XYZ')
        //           = 'honda-vario-160-b-1234-xyz' → bentrok dengan unit soft-deleted.
        $response = $this->post(route('admin.kendaraan.store'), $this->payload([
            'name' => 'Honda Vario',
            'plate_number' => '160 b 1234 xyz',
        ]));

        $response->assertRedirect(route('admin.kendaraan.index'));

        $baru = Vehicle::withTrashed()->where('name', 'Honda Vario')->firstOrFail();
        $this->assertNotSame('honda-vario-160-b-1234-xyz', $baru->slug, 'Slug harus diberi suffix saat bentrok');
        $this->assertStringStartsWith('honda-vario-160-b-1234-xyz', $baru->slug);
    }

    public function test_ganti_foto_menghapus_foto_lama(): void
    {
        $admin = $this->admin();
        $vehicle = Vehicle::factory()->create(['image' => 'storage/vehicles/foto-lama.jpg']);
        Storage::disk('public')->put('vehicles/foto-lama.jpg', 'LAMA');

        $this->put(route('admin.kendaraan.update', $vehicle), [
            'name' => $vehicle->name,
            'brand' => $vehicle->brand,
            'vehicle_type_id' => $this->tipeMatic()->id,
            'plate_number' => $vehicle->plate_number,
            'price_per_day' => $vehicle->price_per_day,
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('baru.jpg', 800, 600),
        ])->assertRedirect(route('admin.kendaraan.index'));

        $vehicle->refresh();
        $this->assertStringStartsWith('storage/vehicles/', $vehicle->image);
        $this->assertNotSame('storage/vehicles/foto-lama.jpg', $vehicle->image);
        Storage::disk('public')->assertMissing('vehicles/foto-lama.jpg', 'Foto lama harus terhapus');
        Storage::disk('public')->assertExists(str_replace('storage/', '', $vehicle->image));
    }
}
