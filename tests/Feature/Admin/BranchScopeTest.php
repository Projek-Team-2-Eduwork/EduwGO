<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Admin cabang hanya melihat/mengelola data cabangnya; superadmin semua cabang.
 */
class BranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private Branch $malang;

    private Branch $batu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->malang = Branch::factory()->create(['name' => 'Cabang Malang']);
        $this->batu = Branch::factory()->create(['name' => 'Cabang Batu']);
    }

    private function unit(Branch $branch, string $name): Vehicle
    {
        return Vehicle::factory()->create(['branch_id' => $branch->id, 'name' => $name]);
    }

    private function booking(Vehicle $vehicle, string $customer, BookingStatus $status = BookingStatus::Pending): Booking
    {
        return Booking::factory()->create(['vehicle_id' => $vehicle->id, 'customer_name' => $customer, 'status' => $status]);
    }

    private function pngUpload(): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent('motor.png', $png);
    }

    // ----- kendaraan -----

    public function test_admin_cabang_hanya_melihat_kendaraan_cabangnya(): void
    {
        $this->unit($this->malang, 'Motor Malang');
        $this->unit($this->batu, 'Motor Batu');
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.kendaraan.index'))
            ->assertOk()
            ->assertSee('Motor Malang')
            ->assertDontSee('Motor Batu');
    }

    public function test_admin_cabang_tidak_bisa_lihat_ubah_hapus_kendaraan_cabang_lain(): void
    {
        $asing = $this->unit($this->batu, 'Motor Batu');
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.kendaraan.show', $asing))->assertForbidden();
        $this->get(route('admin.kendaraan.edit', $asing))->assertForbidden();
        $this->delete(route('admin.kendaraan.destroy', $asing))->assertForbidden();
        $this->assertDatabaseHas('vehicles', ['id' => $asing->id, 'deleted_at' => null]);
    }

    public function test_unit_baru_admin_cabang_selalu_masuk_cabangnya_walau_branch_id_dipalsukan(): void
    {
        $this->actingAsAdmin($this->malang);

        $this->post(route('admin.kendaraan.store'), [
            'name' => 'Motor Baru',
            'brand' => 'Honda',
            'vehicle_type_id' => VehicleType::factory()->create()->id,
            'branch_id' => $this->batu->id, // dicoba masuk ke cabang lain
            'plate_number' => 'N 1234 AB',
            'price_per_day' => 90000,
            'image' => $this->pngUpload(),
            'is_active' => true,
        ])->assertRedirect(route('admin.kendaraan.index'));

        $this->assertSame($this->malang->id, Vehicle::where('plate_number', 'N 1234 AB')->value('branch_id'));
    }

    public function test_admin_cabang_tidak_bisa_memindahkan_unit_ke_cabang_lain(): void
    {
        $unit = $this->unit($this->malang, 'Motor Malang');
        $this->actingAsAdmin($this->malang);

        $this->put(route('admin.kendaraan.update', $unit), [
            'name' => 'Motor Malang',
            'brand' => 'Honda',
            'vehicle_type_id' => $unit->vehicle_type_id,
            'branch_id' => $this->batu->id,
            'plate_number' => $unit->plate_number,
            'price_per_day' => 90000,
            'is_active' => true,
        ])->assertRedirect(route('admin.kendaraan.index'));

        $this->assertSame($this->malang->id, $unit->fresh()->branch_id);
    }

    public function test_superadmin_melihat_semua_cabang_dan_bisa_filter_per_cabang(): void
    {
        $this->unit($this->malang, 'Motor Malang');
        $this->unit($this->batu, 'Motor Batu');
        $this->actingAsSuperadmin();

        $this->get(route('admin.kendaraan.index'))->assertOk()->assertSee('Motor Malang')->assertSee('Motor Batu');
        $this->get(route('admin.kendaraan.index', ['branch' => $this->batu->id]))
            ->assertOk()
            ->assertSee('Motor Batu')
            ->assertDontSee('Motor Malang');
    }

    public function test_superadmin_wajib_memilih_cabang_saat_tambah_unit(): void
    {
        $this->actingAsSuperadmin();

        $this->post(route('admin.kendaraan.store'), [
            'name' => 'Motor Tanpa Cabang',
            'brand' => 'Honda',
            'vehicle_type_id' => VehicleType::factory()->create()->id,
            'plate_number' => 'N 9999 ZZ',
            'price_per_day' => 90000,
            'image' => $this->pngUpload(),
        ])->assertSessionHasErrors('branch_id');
    }

    // ----- pesanan -----

    public function test_admin_cabang_hanya_melihat_pesanan_cabangnya(): void
    {
        $this->booking($this->unit($this->malang, 'Motor Malang'), 'Penyewa Malang');
        $this->booking($this->unit($this->batu, 'Motor Batu'), 'Penyewa Batu');
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.pesanan.index'))
            ->assertOk()
            ->assertSee('Penyewa Malang')
            ->assertDontSee('Penyewa Batu');
    }

    public function test_pesanan_cabang_lain_ditolak_untuk_detail_status_catatan_dan_tunai(): void
    {
        $asing = $this->booking($this->unit($this->batu, 'Motor Batu'), 'Penyewa Batu');
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.pesanan.show', $asing->code))->assertForbidden();
        $this->post(route('admin.pesanan.update', $asing->code), ['to' => 'cancelled', 'reason' => 'iseng'])->assertForbidden();
        $this->post(route('admin.pesanan.notes', $asing->code), ['notes' => 'x'])->assertForbidden();
        $this->post(route('admin.pesanan.pay-cash', $asing->code), ['amount' => 1000])->assertForbidden();
        $this->get(route('admin.pesanan.cek-status', $asing->code))->assertForbidden();

        $this->assertSame(BookingStatus::Pending, $asing->fresh()->status);
    }

    public function test_pesanan_unit_soft_delete_tetap_milik_cabangnya(): void
    {
        $unit = $this->unit($this->malang, 'Motor Lama');
        $booking = $this->booking($unit, 'Penyewa Lama');
        $unit->delete();

        $this->actingAsAdmin($this->batu);
        $this->get(route('admin.pesanan.show', $booking->code))->assertForbidden();

        $this->actingAsAdmin($this->malang);
        $this->get(route('admin.pesanan.show', $booking->code))->assertOk();
    }

    public function test_superadmin_melihat_pesanan_semua_cabang(): void
    {
        $this->booking($this->unit($this->malang, 'Motor Malang'), 'Penyewa Malang');
        $this->booking($this->unit($this->batu, 'Motor Batu'), 'Penyewa Batu');
        $this->actingAsSuperadmin();

        $this->get(route('admin.pesanan.index'))->assertOk()->assertSee('Penyewa Malang')->assertSee('Penyewa Batu');
        $this->get(route('admin.pesanan.index', ['branch' => $this->malang->id]))
            ->assertOk()
            ->assertSee('Penyewa Malang')
            ->assertDontSee('Penyewa Batu');
    }

    // ----- dashboard -----

    public function test_dashboard_admin_cabang_hanya_menghitung_cabangnya(): void
    {
        $unitMalang = $this->unit($this->malang, 'Motor Malang');
        $unitBatu = $this->unit($this->batu, 'Motor Batu');
        $this->unit($this->batu, 'Motor Batu Dua');

        foreach ([[$unitMalang, 100000], [$unitBatu, 250000]] as [$unit, $amount]) {
            $booking = $this->booking($unit, 'X', BookingStatus::Paid);
            Payment::factory()->create(['booking_id' => $booking->id, 'status' => 'paid', 'amount' => $amount, 'paid_at' => now()]);
        }

        $service = new DashboardService;
        $from = now()->startOfDay();
        $to = now()->endOfDay();

        $this->assertSame(1, $service->stats($this->malang->id)['total_vehicles']);
        $this->assertSame(2, $service->stats($this->batu->id)['total_vehicles']);
        $this->assertSame(3, $service->stats()['total_vehicles']);
        $this->assertEquals(100000, $service->revenue($from, $to, null, null, $this->malang->id));
        $this->assertEquals(350000, $service->revenue($from, $to));
        $this->assertCount(1, $service->recentBookings($this->malang->id));
        $this->assertCount(1, $service->topVehicles($this->batu->id));

        $this->actingAsAdmin($this->malang);
        $this->get(route('admin.dashboard', ['branch' => $this->batu->id])) // param diabaikan untuk admin cabang
            ->assertOk()
            ->assertSee('Rp 100.000')
            ->assertDontSee('Rp 350.000');
    }

    public function test_admin_tanpa_cabang_tidak_melihat_data_apa_pun(): void
    {
        $this->unit($this->malang, 'Motor Malang');
        $admin = $this->actingAsAdmin($this->malang);
        $admin->forceFill(['branch_id' => null])->save();

        $this->get(route('admin.kendaraan.index'))->assertOk()->assertDontSee('Motor Malang');
        $this->get(route('admin.kendaraan.create'))->assertForbidden();
    }

    // ----- pengaturan -----

    public function test_admin_cabang_mengubah_profil_cabangnya_sendiri(): void
    {
        $this->actingAsAdmin($this->malang);

        $this->put(route('admin.pengaturan-cabang.update'), [
            'address' => 'Jl. Baru No. 1',
            'phone' => '08111222333',
            'operating_hours' => 'Setiap hari, 07.00 – 22.00 WIB',
            'social_links' => ['instagram' => 'https://instagram.com/malang'],
            'name' => 'Nama Dicoba Ubah',
            'is_active' => false,
        ])->assertSessionHas('success');

        $this->malang->refresh();
        $this->assertSame('Jl. Baru No. 1', $this->malang->address);
        $this->assertSame(['instagram' => 'https://instagram.com/malang'], $this->malang->social_links);
        // Nama & status aktif tetap urusan superadmin
        $this->assertSame('Cabang Malang', $this->malang->name);
        $this->assertTrue($this->malang->is_active);
    }

    public function test_pengaturan_global_dan_tipe_kendaraan_hanya_superadmin(): void
    {
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.pengaturan.edit'))->assertForbidden();
        $this->put(route('admin.pengaturan.update'), ['brand_name' => 'X'])->assertForbidden();
        $this->get(route('admin.tipe-kendaraan.index'))->assertForbidden();
        $this->get(route('admin.pengaturan-cabang.edit'))->assertOk()->assertSee('Cabang Malang');
    }

    public function test_menu_admin_cabang_tidak_menampilkan_menu_superadmin(): void
    {
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('/admin/cabang', false)
            ->assertDontSee('/admin/pengguna', false)
            ->assertDontSee('/admin/tipe-kendaraan', false)
            ->assertSee('/admin/pengaturan-cabang', false);

        $this->actingAsSuperadmin();
        $this->get(route('admin.dashboard'))
            ->assertSee('/admin/cabang', false)
            ->assertSee('/admin/pengguna', false);
    }
}
