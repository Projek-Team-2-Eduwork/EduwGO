<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesananAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_melihat_index_pesanan_dengan_filter_dan_cari()
    {
        $admin = $this->actingAsAdmin();
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
        $response = $this->get(route('admin.pesanan.index'));
        $response->assertOk();
        $response->assertSee('EG.999999');
        $response->assertSee('EG.111111');

        // Tes Filter Status Paid
        $responseFilter = $this->get(route('admin.pesanan.index', ['status' => 'paid']));
        $responseFilter->assertSee('EG.999999');
        $responseFilter->assertDontSee('EG.111111');

        // Tes Pencarian Nama Penyewa
        $responseSearch = $this->get(route('admin.pesanan.index', ['q' => 'Budi']));
        $responseSearch->assertSee('EG.999999');
        $responseSearch->assertDontSee('EG.111111');

        // Tes Pencarian Kode
        $responseCode = $this->get(route('admin.pesanan.index', ['q' => 'EG.1111']));
        $responseCode->assertSee('EG.111111');
        $responseCode->assertDontSee('EG.999999');

        // Tes Pencarian Nama Motor
        $responseMotor = $this->get(route('admin.pesanan.index', ['q' => 'NMAX']));
        $responseMotor->assertSee('EG.999999');
    }

    public function test_pesanan_terlambat_ditandai_dengan_badge_terlambat_di_index()
    {
        $admin = $this->actingAsAdmin();
        $booking = Booking::factory()->rented()->create([
            'end_at' => now()->subHour(),
        ]);

        $response = $this->get(route('admin.pesanan.index'));
        $response->assertOk();
        $response->assertSee('Terlambat');
    }

    public function test_admin_bisa_melihat_detail_pesanan_dan_update_catatan()
    {
        $admin = $this->actingAsAdmin();
        $booking = Booking::factory()->pending()->create([
            'notes' => 'Catatan awal',
        ]);

        $responseShow = $this->get(route('admin.pesanan.show', $booking->code));
        $responseShow->assertOk();
        $responseShow->assertSee($booking->customer_name);
        $responseShow->assertSee('Catatan awal');

        // Post notes
        $responseNotes = $this->post(route('admin.pesanan.notes', $booking->code), [
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
