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
                'expiry_date' => now()->addHour()->toIso8601String(),
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
}
