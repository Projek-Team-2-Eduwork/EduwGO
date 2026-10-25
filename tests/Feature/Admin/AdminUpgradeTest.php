<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUpgradeTest extends TestCase
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

    private function booking(Branch $branch, array $attrs = []): Booking
    {
        $vehicle = Vehicle::factory()->create(['branch_id' => $branch->id]);

        return Booking::factory()->create(['vehicle_id' => $vehicle->id, 'customer_phone' => '081234567890'] + $attrs);
    }

    private function refundPayment(Branch $branch, string $code): Payment
    {
        return Payment::factory()->create([
            'booking_id' => $this->booking($branch, ['code' => $code, 'status' => BookingStatus::Cancelled])->id,
            'status' => 'paid',
            'needs_refund' => true,
            'paid_at' => now(),
            'amount' => 150000,
        ]);
    }

    public function test_jadwal_hari_ini_tampil_dan_terbatas_cabang_admin(): void
    {
        $this->booking($this->malang, ['code' => 'EG.AMBIL1', 'status' => BookingStatus::Paid, 'start_at' => today()->setTime(10, 0), 'end_at' => today()->addDay()->setTime(10, 0)]);
        $this->booking($this->malang, ['code' => 'EG.KMBL01', 'status' => BookingStatus::Rented, 'start_at' => now()->subDay(), 'end_at' => today()->setTime(23, 0)]);
        $this->booking($this->batu, ['code' => 'EG.BATU01', 'status' => BookingStatus::Paid, 'start_at' => today()->setTime(9, 0), 'end_at' => today()->addDay()]);
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Jadwal hari ini')
            ->assertSee('EG.AMBIL1')->assertSee('Serahkan unit')
            ->assertSee('EG.KMBL01')->assertSee('Tandai kembali')
            ->assertDontSee('EG.BATU01');
    }

    public function test_unit_terlambat_jadi_daftar_dengan_aksi(): void
    {
        $this->booking($this->malang, ['code' => 'EG.TELAT1', 'status' => BookingStatus::Rented, 'start_at' => now()->subDays(2), 'end_at' => now()->subHours(3)]);
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('1 unit terlambat')
            ->assertSee('EG.TELAT1')
            ->assertSee('Hubungi')
            ->assertSee('https://wa.me/6281234567890', false);
    }

    public function test_dashboard_tanpa_terlambat_tidak_menampilkan_daftar(): void
    {
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('unit terlambat');
    }

    public function test_pendapatan_per_cabang_hanya_untuk_superadmin(): void
    {
        $payment = ['status' => 'paid', 'paid_at' => now(), 'amount' => 250000];
        Payment::factory()->create(['booking_id' => $this->booking($this->malang, ['status' => BookingStatus::Paid])->id] + $payment);
        $this->actingAsSuperadmin();

        $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Per cabang')->assertSee('Cabang Malang')->assertSee('Cabang Batu')->assertSee('Rp250.000');

        $this->actingAsAdmin($this->malang);
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Per cabang');
    }

    public function test_kartu_status_aktif_memfilter_pesanan(): void
    {
        $this->booking($this->malang, ['code' => 'EG.AKTIF1', 'status' => BookingStatus::Pending]);
        $this->booking($this->malang, ['code' => 'EG.SELESAI', 'status' => BookingStatus::Returned]);
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.pesanan.index', ['status' => 'aktif']))->assertOk()
            ->assertSee('Kelola pesanan')
            ->assertSee('EG.AKTIF1')
            ->assertDontSee('EG.SELESAI');
    }

    public function test_filter_tanggal_pesanan_tidak_valid_ditolak(): void
    {
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.pesanan.index', ['mulai' => 'bukan-tanggal']))->assertSessionHasErrors('mulai');
    }

    public function test_refund_ditandai_dan_hilang_dari_antrian(): void
    {
        $payment = $this->refundPayment($this->malang, 'EG.REFND1');
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.refund.index'))->assertOk()->assertSee('EG.REFND1')->assertSee('Tandai sudah direfund');
        $this->get(route('admin.dashboard'))->assertSee('EG.REFND1');

        $this->post(route('admin.refund.update', $payment))->assertSessionHas('success');
        $this->assertNotNull($payment->fresh()->refunded_at);
        $this->assertSame(0, Payment::awaitingRefund()->count());

        $this->post(route('admin.refund.update', $payment))->assertSessionHas('error');
    }

    public function test_admin_cabang_lain_tidak_bisa_menandai_refund(): void
    {
        $payment = $this->refundPayment($this->batu, 'EG.REFND2');
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.refund.index'))->assertOk()->assertDontSee('EG.REFND2');
        $this->post(route('admin.refund.update', $payment))->assertForbidden();
        $this->assertNull($payment->fresh()->refunded_at);
    }

    public function test_jadwal_armada_menampilkan_blok_booking_cabang_sendiri(): void
    {
        $this->booking($this->malang, ['code' => 'EG.ARMDA1', 'status' => BookingStatus::Paid, 'start_at' => now()->addDays(2), 'end_at' => now()->addDays(3)]);
        $batu = $this->booking($this->batu, ['code' => 'EG.ARMDA2', 'status' => BookingStatus::Paid, 'start_at' => now()->addDay(), 'end_at' => now()->addDays(2)]);
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.jadwal-armada'))->assertOk()
            ->assertSee(route('admin.pesanan.show', 'EG.ARMDA1'), false)
            ->assertDontSee('EG.ARMDA2')
            ->assertDontSee($batu->vehicle->plate_number);
    }

    public function test_jadwal_armada_tanggal_tidak_valid_ditolak(): void
    {
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.jadwal-armada', ['mulai' => 'xx']))->assertSessionHasErrors('mulai');
    }

    public function test_pengguna_dipisah_staf_dan_penyewa_tanpa_kontrol_role_penyewa(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $this->actingAsSuperadmin();
        $admin = User::factory()->create(['name' => 'Staf Malang', 'branch_id' => $this->malang->id]);
        $admin->assignRole('admin');
        User::factory()->create(['name' => 'Penyewa Budi']);

        $this->get(route('admin.pengguna.index'))->assertOk()
            ->assertSee('Staf Malang')->assertDontSee('Penyewa Budi')
            ->assertSee('name="role"', false);

        $this->get(route('admin.pengguna.index', ['tab' => 'penyewa']))->assertOk()
            ->assertSee('Penyewa Budi')->assertDontSee('Staf Malang')
            ->assertDontSee('name="role"', false);
    }

    public function test_detail_penyewa_menampilkan_riwayat_pesanan(): void
    {
        $this->actingAsSuperadmin();
        $penyewa = User::factory()->create(['name' => 'Rina']);
        $this->booking($this->malang, ['code' => 'EG.RINA01', 'user_id' => $penyewa->id]);

        $this->get(route('admin.pengguna.show', $penyewa))->assertOk()
            ->assertSee('Rina')->assertSee('EG.RINA01')
            ->assertDontSee('Kirim link reset password');
    }

    public function test_kirim_link_reset_password_hanya_untuk_staf(): void
    {
        Notification::fake();
        Role::firstOrCreate(['name' => 'admin']);
        $this->actingAsSuperadmin();
        $staf = User::factory()->create(['branch_id' => $this->malang->id]);
        $staf->assignRole('admin');
        $penyewa = User::factory()->create();

        $this->post(route('admin.pengguna.reset-password', $staf))->assertSessionHas('success');
        Notification::assertSentTo($staf, ResetPassword::class);

        $this->post(route('admin.pengguna.reset-password', $penyewa))->assertForbidden();
        Notification::assertNotSentTo($penyewa, ResetPassword::class);
    }

    public function test_badge_sidebar_pesanan_dan_refund(): void
    {
        $this->booking($this->malang, ['status' => BookingStatus::Pending]);
        $this->booking($this->malang, ['status' => BookingStatus::Rented, 'start_at' => now()->subDays(2), 'end_at' => now()->subHour()]);
        $this->booking($this->batu, ['status' => BookingStatus::Pending]);
        $this->refundPayment($this->malang, 'EG.BADGE1');
        $this->actingAsAdmin($this->malang);

        $this->get(route('admin.kendaraan.index'))->assertOk()
            ->assertSee('aria-label="2 perlu tindakan"', false)
            ->assertSee('aria-label="1 perlu tindakan"', false);
    }

    public function test_cabang_tidak_menampilkan_slug(): void
    {
        $this->actingAsSuperadmin();

        $this->get(route('admin.cabang.index'))->assertOk()
            ->assertSee('Cabang Malang')
            ->assertDontSee($this->malang->slug.'</p>', false)
            ->assertSee('Hapus cabang');
    }
}
