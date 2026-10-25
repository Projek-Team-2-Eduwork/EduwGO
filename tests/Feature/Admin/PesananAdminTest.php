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

    /**
     * Angka pada kartu ringkasan berdasarkan labelnya (blok kartu EG-51).
     */
    private function summaryNumber(string $html, string $label): ?string
    {
        return preg_match('/'.$label.'<\/div>\s*<div[^>]*>(\d+)<\/div>/u', $html, $m) ? $m[1] : null;
    }

    public function test_kartu_ringkasan_menampilkan_jumlah_status_yang_cocok()
    {
        $this->actingAsAdmin();
        Booking::factory()->pending()->count(2)->create();
        Booking::factory()->paid()->create();
        Booking::factory()->rented()->create();
        Booking::factory()->returned()->create();
        Booking::factory()->expired()->create();

        $html = $this->get(route('admin.pesanan.index'))->assertOk()->getContent();

        $this->assertSame('2', $this->summaryNumber($html, 'Menunggu pembayaran'));
        $this->assertSame('1', $this->summaryNumber($html, 'Lunas'));
        $this->assertSame('1', $this->summaryNumber($html, 'Sedang disewa'));
        $this->assertSame('1', $this->summaryNumber($html, 'Sudah kembali'));
        $this->assertSame('1', $this->summaryNumber($html, 'Batas bayar terlewat'));
    }

    public function test_klik_kartu_pending_dan_expired_memfilter_tabel()
    {
        $this->actingAsAdmin();
        Booking::factory()->pending()->create(['code' => 'EG.200001']);
        Booking::factory()->pending()->create(['code' => 'EG.200002']);
        Booking::factory()->paid()->create(['code' => 'EG.200003']);
        Booking::factory()->expired()->create(['code' => 'EG.200004']);

        $this->get(route('admin.pesanan.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('EG.200001')
            ->assertSee('EG.200002')
            ->assertDontSee('EG.200003')
            ->assertDontSee('EG.200004');

        $this->get(route('admin.pesanan.index', ['status' => 'expired']))
            ->assertOk()
            ->assertSee('EG.200004')
            ->assertDontSee('EG.200001')
            ->assertDontSee('EG.200003');
    }

    public function test_filter_lunas_hanya_menampilkan_paid()
    {
        $this->actingAsAdmin();
        Booking::factory()->paid()->create(['code' => 'EG.300001']);
        Booking::factory()->rented()->create(['code' => 'EG.300002']);
        Booking::factory()->returned()->create(['code' => 'EG.300003']);
        Booking::factory()->pending()->create(['code' => 'EG.300004']);
        Booking::factory()->expired()->create(['code' => 'EG.300005']);
        Booking::factory()->cancelled()->create(['code' => 'EG.300006']);

        $this->get(route('admin.pesanan.index', ['status' => 'paid']))
            ->assertOk()
            ->assertSee('EG.300001')
            ->assertDontSee('EG.300002')
            ->assertDontSee('EG.300003')
            ->assertDontSee('EG.300004')
            ->assertDontSee('EG.300005')
            ->assertDontSee('EG.300006');
    }

    public function test_klik_kartu_aktif_lagi_melepas_filter()
    {
        $this->actingAsAdmin();
        Booking::factory()->pending()->create(['code' => 'EG.500001']);
        Booking::factory()->paid()->create(['code' => 'EG.500002']);

        $response = $this->get(route('admin.pesanan.index', ['status' => 'pending']));
        $response->assertOk()->assertSee('EG.500001')->assertDontSee('EG.500002');

        // Kartu Pending sedang aktif: href-nya harus tanpa param status (toggle lepas).
        preg_match_all('/<a href="([^"]+)"[^>]*aria-current="true"/', $response->getContent(), $matches);
        $this->assertCount(1, $matches[1]);
        $href = html_entity_decode($matches[1][0]);
        $this->assertStringNotContainsString('status=pending', $href);

        // Klik kartu aktif → kembali menampilkan semua pesanan.
        $this->get($href)->assertOk()->assertSee('EG.500001')->assertSee('EG.500002');
    }
}
