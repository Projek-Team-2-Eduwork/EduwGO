<?php

namespace Tests\Feature\Xendit;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\XenditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentRequestTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-rahasia';

    private const QR_STRING = '00020101021126610014COM.GO-JEK.WWW0118936009143250602202020303UMI04091234567890503039990609123456789';

    protected $user;

    protected $booking;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.xendit.callback_token' => self::TOKEN,
            'services.xendit.secret_key' => 'xnd_development_fake',
        ]);

        $this->user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $this->booking = Booking::factory()->pending()->create([
            'code' => 'BOOK-PR-001',
            'user_id' => $this->user->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->addDays(1),
            'end_at' => now()->addDays(3),
            'duration_days' => 2,
            'total_amount' => 150000,
        ]);
    }

    private function paymentRequestResponse(): array
    {
        return [
            'id' => 'pr-123',
            'reference_id' => 'BOOK-PR-001',
            'status' => 'PENDING',
            'payment_method' => [
                'id' => 'pm-123',
                'type' => 'QRIS',
                'qr_code' => [
                    'qr_string' => self::QR_STRING,
                    'expires_at' => now()->addHour()->toIso8601String(),
                ],
            ],
        ];
    }

    private function webhook(array $payload, ?string $token = self::TOKEN)
    {
        $headers = [];

        if ($token !== null) {
            $headers['x-callback-token'] = $token;
        }

        return $this->postJson('/webhooks/xendit', $payload, $headers);
    }

    private function paymentRequestPayload(string $event, array $dataOverrides = []): array
    {
        return [
            'event' => $event,
            'business_id' => 'business-123',
            'data' => array_merge([
                'id' => 'pr-123',
                'reference_id' => 'BOOK-PR-001',
                'status' => $event === 'payment.paid' ? 'SUCCEEDED' : 'FAILED',
            ], $dataOverrides),
        ];
    }

    private function seedPaymentRequest(): Payment
    {
        return Payment::create([
            'booking_id' => $this->booking->id,
            'method' => 'xendit_payment_request',
            'status' => 'pending',
            'gateway_reference' => 'pr-123',
            'payment_channel' => 'QRIS',
            'expires_at' => now()->addHour(),
            'amount' => 150000,
        ]);
    }

    public function test_create_payment_request_menghasilkan_instruksi_tersimpan()
    {
        Http::fake(['api.xendit.co/*' => Http::response($this->paymentRequestResponse())]);

        $payment = app(XenditService::class)->createPaymentRequest($this->booking, 'QRIS');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/payment_requests')
            && $request['reference_id'] === 'BOOK-PR-001'
            && $request['payment_methods'] === ['QRIS']
            && $request['amount'] === 150000);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'method' => 'xendit_payment_request',
            'payment_channel' => 'QRIS',
            'gateway_reference' => 'pr-123',
            'status' => 'pending',
        ]);

        $payment->refresh();
        $this->assertIsArray($payment->instructions);
        $this->assertNotEmpty($payment->instructions);
        $this->assertStringContainsString(self::QR_STRING, implode(' ', $payment->instructions));
        $this->assertNull($payment->gateway_url, 'Payment Request tidak punya hosted checkout URL');
    }

    public function test_mode_payment_request_mengalihkan_create_invoice()
    {
        config(['services.xendit.mode' => 'payment_request']);
        Http::fake(['api.xendit.co/*' => Http::response($this->paymentRequestResponse())]);

        $payment = app(XenditService::class)->createInvoice($this->booking);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/payment_requests'));
        $this->assertSame('xendit_payment_request', $payment->method);
    }

    public function test_webhook_payment_paid_menandai_paid_dengan_history()
    {
        $this->seedPaymentRequest();

        $this->webhook($this->paymentRequestPayload('payment.paid', [
            'succeeded_at' => '2026-10-12T12:00:00.000Z',
        ]))->assertStatus(200)->assertJson(['status' => 'ok']);

        $payment = Payment::where('gateway_reference', 'pr-123')->first();

        $this->assertSame('paid', $payment->status);
        $this->assertSame(BookingStatus::Paid, $this->booking->refresh()->status);
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $this->booking->id,
            'from_status' => 'pending',
            'to_status' => 'paid',
        ]);
        $this->assertSame('payment.paid', $payment->gateway_payload['event']);
    }

    public function test_webhook_payment_paid_ganda_idempoten()
    {
        $this->seedPaymentRequest();

        $this->webhook($this->paymentRequestPayload('payment.paid', [
            'succeeded_at' => '2026-10-12T12:00:00.000Z',
        ]))->assertStatus(200);

        // Kirim ulang event yang sama persis
        $this->webhook($this->paymentRequestPayload('payment.paid', [
            'succeeded_at' => '2026-10-12T12:00:00.000Z',
        ]))->assertStatus(200);

        $this->assertSame('paid', Payment::where('gateway_reference', 'pr-123')->first()->status);
        $this->assertSame(1, $this->booking->histories()->count(), 'riwayat status tidak boleh dobel');
        $this->assertSame(BookingStatus::Paid, $this->booking->refresh()->status);
    }

    public function test_webhook_payment_failed_tidak_mengubah_status_booking()
    {
        $this->seedPaymentRequest();

        $this->webhook($this->paymentRequestPayload('payment.failed', [
            'failure_code' => 'USER_CANCELLED',
        ]))->assertStatus(200);

        $payment = Payment::where('gateway_reference', 'pr-123')->first();

        $this->assertSame('pending', $payment->status);
        $this->assertSame(BookingStatus::Pending, $this->booking->refresh()->status);
        $this->assertSame(0, $this->booking->histories()->count());
        $this->assertSame('payment.failed', $payment->gateway_payload['event'], 'payload wajib tercatat');
    }

    public function test_webhook_payment_request_tidak_dikenal_404()
    {
        $this->webhook($this->paymentRequestPayload('payment.paid'))->assertStatus(404);
    }

    public function test_webhook_ditolak_403_jika_token_salah()
    {
        $this->seedPaymentRequest();

        $this->webhook($this->paymentRequestPayload('payment.paid'), 'token-salah')->assertStatus(403);
        $this->webhook($this->paymentRequestPayload('payment.paid'), null)->assertStatus(403);

        $this->assertSame('pending', Payment::where('gateway_reference', 'pr-123')->first()->status);
        $this->assertSame(BookingStatus::Pending, $this->booking->refresh()->status);
    }
}
