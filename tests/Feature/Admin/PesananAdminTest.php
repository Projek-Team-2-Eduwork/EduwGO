<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PesananAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    private function getAdmin()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_admin_melihat_index_pesanan_dengan_filter_dan_cari()
    {
        $admin = $this->getAdmin();
        $type = VehicleType::factory()->create(['name' => 'Matic']);
        $vehicle = Vehicle::factory()->create(['name' => 'NMAX', 'vehicle_type_id' => $type->id]);

        $bookingPaid = Booking::factory()->paid()->create([
            'vehicle_id' => $vehicle->id,
            'customer_name' => 'Budi Santoso',
            'code' => 'EG.999999',
        ]);

        $bookingPending = Booking::factory()->pending()->create([
            'customer_name' => 'Agus',
            'code' => 'EG.111111',
        ]);

        // Tes Index Dasar
        $response = $this->actingAs($admin)->get(route('admin.pesanan.index'));
        $response->assertOk();
        $response->assertSee('EG.999999');
        $response->assertSee('EG.111111');

        // Tes Filter Status Paid
        $responseFilter = $this->actingAs($admin)->get(route('admin.pesanan.index', ['status' => 'paid']));
        $responseFilter->assertSee('EG.999999');
        $responseFilter->assertDontSee('EG.111111');

        // Tes Pencarian Nama Penyewa
        $responseSearch = $this->actingAs($admin)->get(route('admin.pesanan.index', ['q' => 'Budi']));
        $responseSearch->assertSee('EG.999999');
        $responseSearch->assertDontSee('EG.111111');

        // Tes Pencarian Kode
        $responseCode = $this->actingAs($admin)->get(route('admin.pesanan.index', ['q' => 'EG.1111']));
        $responseCode->assertSee('EG.111111');
        $responseCode->assertDontSee('EG.999999');

        // Tes Pencarian Nama Motor
        $responseMotor = $this->actingAs($admin)->get(route('admin.pesanan.index', ['q' => 'NMAX']));
        $responseMotor->assertSee('EG.999999');
    }

    public function test_pesanan_terlambat_ditandai_dengan_badge_terlambat_di_index()
    {
        $admin = $this->getAdmin();
        $booking = Booking::factory()->rented()->create([
            'end_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pesanan.index'));
        $response->assertOk();
        $response->assertSee('Terlambat');
    }

    public function test_admin_bisa_melihat_detail_pesanan_dan_update_catatan()
    {
        $admin = $this->getAdmin();
        $booking = Booking::factory()->pending()->create([
            'notes' => 'Catatan awal',
        ]);

        $responseShow = $this->actingAs($admin)->get(route('admin.pesanan.show', $booking->code));
        $responseShow->assertOk();
        $responseShow->assertSee($booking->customer_name);
        $responseShow->assertSee('Catatan awal');

        // Post notes
        $responseNotes = $this->actingAs($admin)->post(route('admin.pesanan.notes', $booking->code), [
            'notes' => 'Catatan tambahan dari admin',
        ]);

        $responseNotes->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'notes' => 'Catatan tambahan dari admin',
        ]);
    }

    public function test_user_biasa_dan_guest_ditolak_mengakses_admin_pesanan()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create();

        // User auth non-admin
        $this->actingAs($user)->get(route('admin.pesanan.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.pesanan.show', $booking->code))->assertForbidden();
        $this->actingAs($user)->post(route('admin.pesanan.notes', $booking->code), ['notes' => 'tes'])->assertForbidden();

        // Guest (belum login)
        auth()->logout();
        $this->get(route('admin.pesanan.index'))->assertRedirect();
    }
}
