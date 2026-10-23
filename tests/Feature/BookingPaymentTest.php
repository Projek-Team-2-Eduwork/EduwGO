<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $booking;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_waiting_page_creates_invoice_and_shows_countdown()
    {
        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response([
                'id' => 'inv_123',
                'invoice_url' => 'https://checkout.xendit.co/web/inv_123',
                'expiry_date' => now()->addHour()->toISOString(),
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('booking.waiting', $this->booking->code));

        $response->assertStatus(200);
        $response->assertViewIs('booking.waiting');

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'method' => 'xendit_invoice',
            'status' => 'pending',
            'gateway_reference' => 'inv_123',
        ]);
    }

    public function test_create_invoice_is_idempotent()
    {
        Payment::create([
            'booking_id' => $this->booking->id,
            'method' => 'xendit_invoice',
            'status' => 'pending',
            'gateway_reference' => 'inv_existing_001',
            'gateway_url' => 'https://checkout.xendit.co/web/inv_existing_001',
            'expires_at' => now()->addMinutes(30),
            'gateway_payload' => json_encode([]),
            'amount' => 150000,
        ]);

        // Mock API: Tidak boleh dipanggil
        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response(['id' => 'inv_new'], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('booking.waiting', $this->booking->code));

        $response->assertStatus(200);
        Http::assertSentCount(0); // Memastikan Xendit API tidak dipanggil ulang
    }

    public function test_redirect_if_status_not_pending()
    {
        $this->booking->update(['status' => BookingStatus::Paid]);

        $response = $this->actingAs($this->user)
            ->get(route('booking.waiting', $this->booking->code));

        $response->assertRedirect(route('booking.success', $this->booking->code));
    }

    public function test_expiry_date_utc_is_stored_in_app_timezone()
    {
        // API Xendit asli mengirim expiry_date dalam format UTC (Z), bukan offset lokal
        $expiryUtc = now()->addHour()->startOfSecond();

        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response([
                'id' => 'inv_utc_001',
                'invoice_url' => 'https://checkout.xendit.co/web/inv_utc_001',
                'expiry_date' => $expiryUtc->toISOString(),
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->get(route('booking.waiting', $this->booking->code))
            ->assertStatus(200);

        $payment = Payment::where('booking_id', $this->booking->id)->firstOrFail();

        // Instan yang sama dengan respons API — tidak boleh miring timezone
        $this->assertSame(
            $expiryUtc->getTimestamp(),
            $payment->expires_at->getTimestamp(),
            'expires_at ('.$payment->expires_at->toIso8601String().') harus sama dengan expiry_date API ('.$expiryUtc->toIso8601String().')'
        );

        // Karena tidak dianggap kedaluwarsa, invoice tidak dibuat ulang saat halaman dibuka lagi
        $this->actingAs($this->user)
            ->get(route('booking.waiting', $this->booking->code))
            ->assertStatus(200);

        Http::assertSentCount(1);
        $this->assertSame(1, Payment::where('booking_id', $this->booking->id)->count());
    }
}
