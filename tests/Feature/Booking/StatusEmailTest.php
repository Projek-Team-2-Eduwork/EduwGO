<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Mail\BookingStatusMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\XenditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StatusEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(XenditService::class, function ($mock) {
            $mock->shouldReceive('createInvoice')->andReturnUsing(function ($booking) {
                return Payment::factory()->create([
                    'booking_id' => $booking->id,
                    'status' => 'pending',
                ]);
            });
        });
    }

    public function test_checkout_sukses_mengirim_email_pending()
    {
        Mail::fake();

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('checkout.store', $vehicle->id), [
            'start' => now()->addDays(2)->toISOString(),
            'days' => 2,
            'customer_name' => 'Budi',
            'customer_phone' => '08123',
        ]);

        Mail::assertSent(BookingStatusMail::class, function (BookingStatusMail $mail) use ($user) {
            return $mail->hasTo($user->email) &&
                   str_contains($mail->subjectText, 'Selesaikan pembayaran');
        });

        Mail::assertSentTimes(BookingStatusMail::class, 1);
    }

    public function test_transisi_status_tertentu_mengirimkan_email_yang_sesuai()
    {
        Mail::fake();

        $booking = Booking::factory()->pending()->create();
        $service = app(BookingService::class);

        // Paid
        $service->transition($booking, BookingStatus::Paid, null, 'Dibayar');
        Mail::assertSent(BookingStatusMail::class, function ($mail) {
            return str_contains($mail->subjectText, 'Pembayaran diterima');
        });

        // Cancelled
        $booking = Booking::factory()->pending()->create();
        $service->transition($booking, BookingStatus::Cancelled, null, 'Batal');
        Mail::assertSent(BookingStatusMail::class, function ($mail) {
            return str_contains($mail->subjectText, 'Booking dibatalkan');
        });

        // Expired
        $booking = Booking::factory()->pending()->create();
        $service->transition($booking, BookingStatus::Expired, null, 'Expired');
        Mail::assertSent(BookingStatusMail::class, function ($mail) {
            return str_contains($mail->subjectText, 'Waktu habis');
        });
    }

    public function test_transisi_ke_rented_atau_returned_tidak_mengirim_email()
    {
        Mail::fake();

        $booking = Booking::factory()->paid()->create();
        $service = app(BookingService::class);

        $service->transition($booking, BookingStatus::Rented, null, 'Sewa jalan');
        Mail::assertNotSent(BookingStatusMail::class);

        $service->transition($booking, BookingStatus::Returned, null, 'Selesai');
        Mail::assertNotSent(BookingStatusMail::class);
    }
}
