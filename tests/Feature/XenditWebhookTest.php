<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XenditWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-rahasia';

    protected $user;

    protected $booking;

    protected $payment;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.xendit.callback_token' => self::TOKEN]);

        $this->user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $this->booking = Booking::factory()->create([
            'code' => 'BOOK-TEST-001',
            'user_id' => $this->user->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->addDays(1),
            'end_at' => now()->addDays(3),
            'duration_days' => 2,
            'total_amount' => 150000,
            'status' => BookingStatus::Pending,
        ]);

        $this->payment = Payment::create([
            'booking_id' => $this->booking->id,
            'method' => 'xendit_invoice',
            'status' => 'pending',
            'gateway_reference' => 'inv_123',
            'gateway_url' => 'https://checkout.xendit.co/web/inv_123',
            'expires_at' => now()->addHour(),
            'amount' => 150000,
        ]);
    }

    /**
     * Kirim POST ke endpoint webhook dengan token tertentu (null = tanpa header).
     */
    private function webhook(array $payload, ?string $token = self::TOKEN)
    {
        $headers = [];

        if ($token !== null) {
            $headers['x-callback-token'] = $token;
        }

        return $this->postJson('/webhooks/xendit', $payload, $headers);
    }

    private function invoicePayload(string $status = 'PAID'): array
    {
        return [
            'id' => 'inv_123',
            'external_id' => 'BOOK-TEST-001',
            'status' => $status,
            'paid_at' => '2026-09-27T12:00:00.000Z',
            'paid_amount' => 150000,
        ];
    }

    public function test_webhook_rejects_invalid_token()
    {
        $this->webhook($this->invoicePayload(), 'token-salah')->assertStatus(403);
        $this->webhook($this->invoicePayload(), null)->assertStatus(403);

        $this->assertSame('pending', $this->payment->refresh()->status);
        $this->assertSame(BookingStatus::Pending, $this->booking->refresh()->status);
        $this->assertSame(0, $this->booking->histories()->count());
    }

    public function test_paid_webhook_marks_payment_and_booking_paid()
    {
        $this->webhook($this->invoicePayload('PAID'))
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);

        $payment = $this->payment->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame(
            Carbon::parse('2026-09-27T12:00:00.000Z')->getTimestamp(),
            $payment->paid_at->getTimestamp(),
            'paid_at harus sama dengan timestamp paid_at payload (UTC)'
        );

        $this->assertSame(BookingStatus::Paid, $this->booking->refresh()->status);
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $this->booking->id,
            'from_status' => 'pending',
            'to_status' => 'paid',
        ]);

        // Raw payload tersimpan di payments.gateway_payload
        $this->assertSame('inv_123', $payment->gateway_payload['id']);
        $this->assertSame('PAID', $payment->gateway_payload['status']);
    }

    public function test_settled_webhook_also_marks_paid()
    {
        $this->webhook($this->invoicePayload('SETTLED'))->assertStatus(200);

        $this->assertSame('paid', $this->payment->refresh()->status);
        $this->assertSame(BookingStatus::Paid, $this->booking->refresh()->status);
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $this->booking->id,
            'to_status' => 'paid',
        ]);
    }

    public function test_expired_webhook_marks_payment_and_booking_expired()
    {
        $this->webhook($this->invoicePayload('EXPIRED'))
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);

        $payment = $this->payment->refresh();

        $this->assertSame('expired', $payment->status);
        $this->assertSame(BookingStatus::Expired, $this->booking->refresh()->status);
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $this->booking->id,
            'from_status' => 'pending',
            'to_status' => 'expired',
        ]);
        $this->assertSame('EXPIRED', $payment->gateway_payload['status']);
    }

    public function test_duplicate_paid_webhook_is_idempotent()
    {
        $this->webhook($this->invoicePayload('PAID'))->assertStatus(200);
        $paidAt = $this->payment->refresh()->paid_at->getTimestamp();

        // Kirim ulang webhook yang sama persis
        $this->webhook($this->invoicePayload('PAID'))->assertStatus(200);

        $payment = $this->payment->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame($paidAt, $payment->paid_at->getTimestamp(), 'paid_at tidak boleh berubah saat webhook dobel');
        $this->assertSame(1, $this->booking->histories()->count(), 'riwayat status tidak boleh dobel');
        $this->assertSame(BookingStatus::Paid, $this->booking->refresh()->status);
    }

    public function test_duplicate_expired_webhook_is_idempotent()
    {
        $this->webhook($this->invoicePayload('EXPIRED'))->assertStatus(200);
        $this->webhook($this->invoicePayload('EXPIRED'))->assertStatus(200);

        $this->assertSame('expired', $this->payment->refresh()->status);
        $this->assertSame(BookingStatus::Expired, $this->booking->refresh()->status);
        $this->assertSame(1, $this->booking->histories()->count(), 'riwayat status tidak boleh dobel');
    }

    public function test_paid_webhook_when_booking_not_pending_does_not_force_transition()
    {
        // Booking sudah dibatalkan lebih dulu (pending → cancelled)
        $this->booking->update(['status' => BookingStatus::Cancelled]);

        $this->webhook($this->invoicePayload('PAID'))->assertStatus(200);

        // Payment tetap tercatat lunas, tapi status booking tidak dipaksa berubah
        $this->assertSame('paid', $this->payment->refresh()->status);
        $this->assertSame(BookingStatus::Cancelled, $this->booking->refresh()->status);
        $this->assertSame(0, $this->booking->histories()->count());
    }

    public function test_unknown_invoice_returns_404_and_changes_nothing()
    {
        $this->webhook([
            'id' => 'inv_tidak_dikenal',
            'external_id' => 'BOOK-LAIN-999',
            'status' => 'PAID',
            'paid_at' => '2026-09-27T12:00:00.000Z',
        ])->assertStatus(404);

        $this->assertSame('pending', $this->payment->refresh()->status);
        $this->assertSame(BookingStatus::Pending, $this->booking->refresh()->status);
        $this->assertSame(0, $this->booking->histories()->count());
    }
}
