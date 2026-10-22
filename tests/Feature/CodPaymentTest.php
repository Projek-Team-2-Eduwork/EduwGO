<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CodPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.xendit.secret_key' => 'xnd_development_fake']);

        $this->user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $this->booking = Booking::factory()->pending()->create([
            'code' => 'BOOK-COD-001',
            'user_id' => $this->user->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->addDays(1),
            'end_at' => now()->addDays(3),
            'duration_days' => 2,
            'total_amount' => 150000,
        ]);
    }

    public function test_kanal_bayar_di_tempat_tampil_di_pilihan_metode(): void
    {
        Http::fake(['api.xendit.co/*' => Http::response([
            'payment_request_id' => 'pr-qris-cod-1',
            'status' => 'REQUIRES_ACTION',
            'channel_code' => 'QRIS',
            'channel_properties' => ['expires_at' => now()->addHour()->toIso8601String()],
            'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => '0002010102112661']],
        ])]);

        $this->actingAs($this->user)
            ->get(route('booking.bayar', 'BOOK-COD-001'))
            ->assertOk()
            ->assertSee('Bayar di Tempat')
            ->assertSee('metode=COD', false);
    }

    public function test_pilih_cod_mencatat_tunai_pending_tanpa_memanggil_gateway(): void
    {
        Http::fake();

        $response = $this->actingAs($this->user)
            ->get(route('booking.bayar', ['code' => 'BOOK-COD-001', 'metode' => 'COD']));

        $response->assertOk()
            ->assertSee('Bayar di Tempat (COD)')
            ->assertSee('Pembayaran tunai dicatat oleh admin')
            ->assertDontSee('Saya Sudah Bayar');

        Http::assertNothingSent();

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'method' => 'cash',
            'status' => 'pending',
            'payment_channel' => 'COD',
            'amount' => 150000,
        ]);

        // Booking tetap pending sampai admin mencatat uangnya.
        $this->assertEquals(BookingStatus::Pending, $this->booking->fresh()->status);
    }

    public function test_pilih_cod_berulang_tidak_menumpuk_baris_pembayaran(): void
    {
        Http::fake();

        $this->actingAs($this->user)->get(route('booking.bayar', ['code' => 'BOOK-COD-001', 'metode' => 'COD']));
        $this->actingAs($this->user)->get(route('booking.bayar', ['code' => 'BOOK-COD-001', 'metode' => 'COD']));

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_admin_menandai_cod_lunas_tanpa_membuat_baris_ganda(): void
    {
        Http::fake();

        // Penyewa memilih COD → satu baris tunai pending.
        $this->actingAs($this->user)
            ->get(route('booking.bayar', ['code' => 'BOOK-COD-001', 'metode' => 'COD']));

        $payment = Payment::where('booking_id', $this->booking->id)->firstOrFail();
        $this->actingAsAdmin();

        $this->get(route('admin.pesanan.show', 'BOOK-COD-001'))
            ->assertOk()
            ->assertSee('Tandai Sudah Dibayar (COD)')
            ->assertSee('Tandai Lunas');

        // Nominal dari input number berbentuk desimal ("150000.00") dan tetap
        // harus diterima. Baris COD yang sama ditandai lunas, bukan diduplikasi.
        $this->post(route('admin.pesanan.pay-cash', 'BOOK-COD-001'), [
            'amount' => '150000.00',
            'note' => 'Diterima di kasir',
        ])->assertRedirect(route('admin.pesanan.show', 'BOOK-COD-001'))
            ->assertSessionHas('success');

        $this->assertEquals(BookingStatus::Paid, $this->booking->fresh()->status);
        $this->assertDatabaseCount('payments', 1);

        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('COD', $payment->payment_channel);
        $this->assertNotNull($payment->paid_at);
        $this->assertEquals(150000, (float) $payment->amount);

        $history = $this->booking->histories()->latest('id')->first();
        $this->assertStringContainsString('Bayar tunai COD', $history->note);
    }

    public function test_admin_hanya_melihat_pembayaran_terakhir(): void
    {
        Role::firstOrCreate(['name' => 'admin']);

        $admin = User::factory()->create(['branch_id' => $this->defaultBranch()->id]);
        $admin->assignRole('admin');

        // Percobaan online lama yang kedaluwarsa, lalu tunai yang benar-benar lunas.
        Payment::create([
            'booking_id' => $this->booking->id,
            'method' => 'xendit_invoice',
            'status' => 'expired',
            'gateway_reference' => 'inv_lama_123',
            'amount' => 150000,
            'expires_at' => now()->subHour(),
        ]);
        Payment::create([
            'booking_id' => $this->booking->id,
            'method' => 'cash',
            'status' => 'paid',
            'payment_channel' => 'COD',
            'amount' => 150000,
            'paid_at' => now(),
        ]);

        $this->booking->update(['status' => BookingStatus::Paid]);

        $this->actingAs($admin)
            ->get(route('admin.pesanan.show', 'BOOK-COD-001'))
            ->assertOk()
            ->assertSee('Tunai (bayar di tempat)')
            ->assertDontSee('inv_lama_123')
            ->assertDontSee('Cek Status Xendit');
    }
}
