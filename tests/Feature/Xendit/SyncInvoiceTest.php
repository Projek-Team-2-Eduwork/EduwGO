<?php

namespace Tests\Feature\Xendit;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SyncInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_sinkronisasi_ke_paid_sukses()
    {
        $admin = User::factory()->create(['branch_id' => $this->defaultBranch()->id]);
        $admin->assignRole('admin');

        $booking = Booking::factory()->pending()->create();
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'pending',
            'gateway_reference' => 'inv_999',
            'expires_at' => now()->addHour(),
        ]);

        // Palsukan API Invoice v2 supaya test tidak memanggil Xendit asli
        config(['services.xendit.secret_key' => 'xnd_development_fake']);
        Http::fake([
            'api.xendit.co/v2/invoices/inv_999' => Http::response([
                'id' => 'inv_999',
                'status' => 'PAID',
                'paid_at' => now()->toISOString(),
            ]),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pesanan.cek-status', $booking->code));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals(BookingStatus::Paid, $booking->status);

        $payment = $booking->latestPayment;
        $this->assertEquals('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'to_status' => BookingStatus::Paid->value,
        ]);
    }

    public function test_cek_status_admin_memakai_payment_request_v3()
    {
        config([
            'services.xendit.mode' => 'payment_request',
            'services.xendit.secret_key' => 'xnd_development_fake',
        ]);
        $admin = User::factory()->create(['branch_id' => $this->defaultBranch()->id]);
        $admin->assignRole('admin');

        $booking = Booking::factory()->pending()->create();
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'method' => 'xendit_payment_request',
            'payment_channel' => 'QRIS',
            'status' => 'pending',
            'gateway_reference' => 'pr-admin-1',
            'expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'api.xendit.co/v3/payment_requests/pr-admin-1' => Http::response([
                'payment_request_id' => 'pr-admin-1',
                'status' => 'SUCCEEDED',
            ]),
        ]);

        $this->actingAs($admin)->get(route('admin.pesanan.cek-status', $booking->code))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(BookingStatus::Paid, $booking->fresh()->status);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/v2/invoices/'));
    }

    public function test_cek_status_gagal_menampilkan_pesan_generik_tanpa_detail_xendit()
    {
        config(['services.xendit.secret_key' => 'xnd_development_fake']);
        $admin = User::factory()->create(['branch_id' => $this->defaultBranch()->id]);
        $admin->assignRole('admin');

        $booking = Booking::factory()->pending()->create();
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'pending',
            'gateway_reference' => 'inv_rahasia',
            'expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'api.xendit.co/*' => Http::response(['error_code' => 'INVALID_API_KEY', 'message' => 'detail-internal-xendit'], 401),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pesanan.cek-status', $booking->code));

        $response->assertSessionHas('error', 'Gagal mengecek status ke Xendit. Coba lagi beberapa saat.');
        $this->assertStringNotContainsString('detail-internal-xendit', (string) session('error'));
        $this->assertEquals(BookingStatus::Pending, $booking->fresh()->status);
    }

    public function test_user_biasa_ditolak()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->pending()->create();

        $response = $this->actingAs($user)->get(route('admin.pesanan.cek-status', $booking->code));

        $response->assertForbidden();
    }

    public function test_halaman_show_tampil_dengan_tombol_cek_status()
    {
        $admin = User::factory()->create(['branch_id' => $this->defaultBranch()->id]);
        $admin->assignRole('admin');

        $booking = Booking::factory()->pending()->create();
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'pending',
            'gateway_reference' => 'inv_888',
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pesanan.show', $booking->code));

        $response->assertOk();
        $response->assertSee('Cek Status Xendit');
    }

    public function test_tombol_tidak_tampil_jika_bukan_pending()
    {
        $admin = User::factory()->create(['branch_id' => $this->defaultBranch()->id]);
        $admin->assignRole('admin');

        $booking = Booking::factory()->paid()->create();
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'paid',
            'gateway_reference' => 'inv_777',
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pesanan.show', $booking->code));

        $response->assertOk();
        $response->assertDontSee('Cek Status Xendit');
    }
}
