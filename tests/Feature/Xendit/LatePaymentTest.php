<?php

namespace Tests\Feature\Xendit;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Booking Expired/Cancelled menutup payment pending di Xendit; PAID terlambat
 * ditandai perlu refund (audit H3/C07) dan status payment monoton (C06).
 */
class LatePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-rahasia';

    protected User $user;

    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.xendit.callback_token' => self::TOKEN,
            'services.xendit.secret_key' => 'xnd_development_fake',
        ]);

        $this->user = User::factory()->create();
        $this->booking = Booking::factory()->pending()->create([
            'user_id' => $this->user->id,
            'vehicle_id' => Vehicle::factory()->create(['branch_id' => $this->defaultBranch()->id])->id,
            'total_amount' => 150000,
        ]);
    }

    private function payment(string $method, string $reference, string $status = 'pending'): Payment
    {
        return Payment::create([
            'booking_id' => $this->booking->id,
            'method' => $method,
            'status' => $status,
            'gateway_reference' => $reference,
            'amount' => 150000,
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    public function test_penyewa_batal_menutup_semua_payment_pending_di_xendit()
    {
        $qris = $this->payment('xendit_payment_request', 'pr-qris');
        $kartu = $this->payment('xendit_invoice', 'inv_kartu');
        Http::fake(['api.xendit.co/*' => Http::response(['status' => 'CANCELED'])]);

        $this->actingAs($this->user)->post(route('booking.cancel', $this->booking->code))->assertRedirect();

        $this->assertSame(BookingStatus::Cancelled, $this->booking->fresh()->status);
        $this->assertSame('expired', $qris->fresh()->status);
        $this->assertSame('expired', $kartu->fresh()->status);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.xendit.co/v3/payment_requests/pr-qris/cancel');
        Http::assertSent(fn ($request) => $request->url() === 'https://api.xendit.co/invoices/inv_kartu/expire!');
    }

    public function test_gagal_batal_di_xendit_tidak_menggagalkan_pembatalan()
    {
        $qris = $this->payment('xendit_payment_request', 'pr-qris');
        Http::fake(['api.xendit.co/*' => Http::response(['error_code' => 'SERVER_ERROR'], 500)]);

        $this->actingAs($this->user)->post(route('booking.cancel', $this->booking->code))->assertRedirect();

        $this->assertSame(BookingStatus::Cancelled, $this->booking->fresh()->status);
        $this->assertSame('expired', $qris->fresh()->status);
    }

    public function test_paid_untuk_booking_expired_ditandai_perlu_refund_tanpa_menghidupkan_booking()
    {
        $qris = $this->payment('xendit_payment_request', 'pr-telat');
        Http::fake();

        // Booking expired lebih dulu (payment ikut ditutup), lalu penyewa tetap membayar QR lama
        $this->booking->forceFill(['created_at' => now()->subMinutes(61)])->save();
        $this->artisan('bookings:expire')->assertSuccessful();
        $this->assertSame('expired', $qris->fresh()->status);

        $this->postJson('/webhooks/xendit', [
            'event' => 'payment.capture',
            'data' => ['payment_request_id' => 'pr-telat', 'status' => 'SUCCEEDED'],
        ], ['x-callback-token' => self::TOKEN])->assertOk();

        $qris->refresh();
        $this->assertSame('paid', $qris->status);
        $this->assertTrue($qris->needs_refund);
        $this->assertSame(BookingStatus::Expired, $this->booking->fresh()->status);

        // Admin melihat peringatan refund di detail pesanan
        $this->actingAsAdmin();
        $this->get(route('admin.pesanan.show', $this->booking->code))
            ->assertOk()
            ->assertSee('Perlu refund')
            ->assertSee('pr-telat');
    }

    public function test_paid_normal_tidak_ditandai_refund_dan_menutup_payment_lain()
    {
        $qris = $this->payment('xendit_payment_request', 'pr-qris');
        $va = $this->payment('xendit_payment_request', 'pr-va');
        Http::fake();

        app(PaymentService::class)->applyInvoiceStatus($qris, ['status' => 'PAID']);

        $this->assertSame(BookingStatus::Paid, $this->booking->fresh()->status);
        $this->assertFalse($qris->fresh()->needs_refund);
        // VA yang masih terbuka ditutup supaya tidak terbayar dobel
        $this->assertSame('expired', $va->fresh()->status);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.xendit.co/v3/payment_requests/pr-va/cancel');
    }

    public function test_event_expired_tidak_menurunkan_payment_paid()
    {
        $payment = $this->payment('xendit_invoice', 'inv_lunas');
        Http::fake();
        $service = app(PaymentService::class);

        $service->applyInvoiceStatus($payment, ['status' => 'PAID', 'paid_at' => '2026-10-25T03:00:00Z']);
        $service->applyInvoiceStatus($payment, ['status' => 'EXPIRED']);

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('PAID', $payment->gateway_payload['status'], 'payload bukti bayar tidak boleh ditimpa');
        $this->assertSame(BookingStatus::Paid, $this->booking->fresh()->status);
    }

    public function test_kandidat_cron_basi_tidak_meng_expire_booking_yang_sudah_dibayar()
    {
        $payment = $this->payment('xendit_invoice', 'inv_balapan');
        $this->booking->forceFill(['created_at' => now()->subMinutes(61)])->save();
        Http::fake();
        $service = app(PaymentService::class);

        // Cron sudah mengambil kandidat (masih Pending di memori)...
        $kandidat = Booking::paymentDeadlinePassed()->firstOrFail();

        // ...lalu webhook PAID commit lebih dulu
        $service->applyInvoiceStatus($payment, ['status' => 'PAID']);

        $this->assertFalse($service->expireIfDeadlinePassed($kandidat));
        $this->assertSame(BookingStatus::Paid, $this->booking->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->status);
    }
}
