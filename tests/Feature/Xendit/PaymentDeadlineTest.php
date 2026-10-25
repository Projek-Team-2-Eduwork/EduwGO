<?php

namespace Tests\Feature\Xendit;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Batas bayar tetap dari bookings.created_at + invoice_minutes (audit H2/C05).
 */
class PaymentDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.xendit.mode' => 'payment_request',
            'services.xendit.secret_key' => 'xnd_development_fake',
        ]);

        $this->user = User::factory()->create();
    }

    private function bookingPending(int $menitLalu): Booking
    {
        return Booking::factory()->pending()->create([
            'user_id' => $this->user->id,
            'vehicle_id' => Vehicle::factory()->create()->id,
            'start_at' => now()->addDays(1),
            'end_at' => now()->addDays(2),
            'created_at' => now()->subMinutes($menitLalu),
        ]);
    }

    public function test_ganti_metode_tidak_memperpanjang_batas_bayar_booking()
    {
        $booking = $this->bookingPending(50);
        $deadline = $booking->created_at->copy()->addMinutes(60);

        Http::fake([
            'api.xendit.co/v3/payment_requests' => Http::response([
                'payment_request_id' => 'pr-bca-1',
                'status' => 'REQUIRES_ACTION',
                // Gateway memberi masa berlaku default yang lebih panjang dari batas booking
                'channel_properties' => ['expires_at' => now()->addHours(24)->toIso8601String()],
                'actions' => [['descriptor' => 'VIRTUAL_ACCOUNT_NUMBER', 'value' => '8808123']],
            ]),
        ]);

        $this->actingAs($this->user)
            ->get(route('booking.bayar', ['code' => $booking->code, 'metode' => 'BCA_VIRTUAL_ACCOUNT']))
            ->assertOk();

        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame($deadline->getTimestamp(), $payment->expires_at->getTimestamp());
    }

    public function test_qris_dikirim_dengan_expires_at_sama_dengan_batas_booking()
    {
        $booking = $this->bookingPending(30);
        $deadline = $booking->created_at->copy()->addMinutes(60);

        Http::fake([
            'api.xendit.co/v3/payment_requests' => Http::response([
                'payment_request_id' => 'pr-qris-1',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['descriptor' => 'QR_STRING', 'value' => '000201010212']],
            ]),
        ]);

        $this->actingAs($this->user)
            ->get(route('booking.bayar', ['code' => $booking->code, 'metode' => 'QRIS']))
            ->assertOk();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v3/payment_requests')
            && Carbon::parse($request['channel_properties']['expires_at'])->getTimestamp() === $deadline->getTimestamp());
    }

    public function test_buka_halaman_bayar_setelah_batas_lewat_tidak_membuat_payment_baru()
    {
        $booking = $this->bookingPending(61);
        Http::fake();

        $this->actingAs($this->user)
            ->get(route('booking.bayar', ['code' => $booking->code, 'metode' => 'DANA']))
            ->assertRedirect(route('booking.failed', $booking->code));

        $this->assertSame(BookingStatus::Expired, $booking->fresh()->status);
        $this->assertSame(0, Payment::where('booking_id', $booking->id)->count());
        Http::assertNothingSent();
    }

    public function test_cron_expire_dari_created_at_meski_payment_terbaru_masih_berlaku()
    {
        $booking = $this->bookingPending(61);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'method' => 'xendit_payment_request',
            'status' => 'pending',
            'gateway_reference' => 'pr-baru',
            'expires_at' => now()->addMinutes(50), // dibuat belakangan, belum kedaluwarsa
        ]);
        Http::fake();

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertSame(BookingStatus::Expired, $booking->fresh()->status);
        $this->assertSame('expired', $payment->fresh()->status);
    }

    public function test_payment_expired_sebelum_batas_booking_tidak_melepas_unit()
    {
        $booking = $this->bookingPending(10);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'method' => 'xendit_invoice',
            'status' => 'pending',
            'gateway_reference' => 'inv_ewallet_pendek',
        ]);
        config(['services.xendit.callback_token' => 'token-rahasia']);

        $this->postJson('/webhooks/xendit', ['id' => 'inv_ewallet_pendek', 'status' => 'EXPIRED'], ['x-callback-token' => 'token-rahasia'])
            ->assertOk();

        // Payment itu saja yang kedaluwarsa; penyewa masih boleh pilih metode lain
        $this->assertSame('expired', $payment->fresh()->status);
        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
    }

    public function test_checkout_ditolak_bila_sudah_punya_dua_booking_pending()
    {
        $this->bookingPending(5);
        $this->bookingPending(10);
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        Http::fake();

        $response = $this->actingAs($this->user)->post(route('checkout.store', $vehicle->id), [
            'start' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'days' => 1,
            'customer_name' => 'Budi',
            'customer_phone' => '081234567890',
        ]);

        $response->assertSessionHasErrors(['start' => 'Kamu masih punya 2 pesanan yang menunggu pembayaran. Selesaikan atau batalkan pesanan itu dulu sebelum membuat pesanan baru.']);
        $this->assertSame(2, Booking::where('user_id', $this->user->id)->count());
        Http::assertNothingSent();
    }

    public function test_booking_pending_yang_lewat_batas_tidak_dihitung_dalam_limit()
    {
        $this->bookingPending(5);
        $this->bookingPending(90); // menunggu di-expire cron
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        config(['services.xendit.mode' => 'invoice']);
        Http::fake([
            'api.xendit.co/v2/invoices' => Http::response([
                'id' => 'inv_limit',
                'invoice_url' => 'https://checkout.xendit.co/web/inv_limit',
                'expiry_date' => now()->addHour()->toISOString(),
            ]),
        ]);

        $this->actingAs($this->user)->post(route('checkout.store', $vehicle->id), [
            'start' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'days' => 1,
            'customer_name' => 'Budi',
            'customer_phone' => '081234567890',
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, Booking::where('user_id', $this->user->id)->count());
    }

    public function test_halaman_bayar_dibatasi_throttle()
    {
        $booking = Booking::factory()->paid()->create(['user_id' => $this->user->id]);

        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($this->user)->get(route('booking.bayar', $booking->code))->assertRedirect();
        }

        $this->actingAs($this->user)->get(route('booking.bayar', $booking->code))->assertStatus(429);
    }
}
