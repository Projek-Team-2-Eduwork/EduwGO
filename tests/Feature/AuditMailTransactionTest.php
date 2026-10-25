<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\XenditService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuditMailTransactionTest extends TestCase
{
    use DatabaseMigrations;

    public function test_smtp_failure_cannot_fail_checkout_or_transition_and_runs_after_commit(): void
    {
        $levels = [];
        Mail::shouldReceive('to')->twice()->andReturnUsing(function () use (&$levels) {
            $levels[] = DB::transactionLevel();
            throw new \RuntimeException('SMTP unavailable');
        });
        $this->mock(XenditService::class)->shouldReceive('createInvoice')->once()->andReturnUsing(fn ($booking) => Payment::factory()->create(['booking_id' => $booking->id]));
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $this->actingAs($user)->post(route('checkout.store', $vehicle->id), [
            'start' => now()->addDays(2)->toISOString(), 'days' => 1,
            'customer_name' => 'Budi', 'customer_phone' => '081234567890',
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $booking = Booking::firstOrFail();
        app(BookingService::class)->transition($booking, BookingStatus::Paid);
        $this->assertSame(BookingStatus::Paid, $booking->fresh()->status);
        $this->assertSame([0, 0], $levels);
        $this->assertSame(2, $booking->histories()->count());
    }

    public function test_rolled_back_transition_does_not_send_email(): void
    {
        Mail::fake();
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        DB::beginTransaction();
        app(BookingService::class)->transition($booking, BookingStatus::Paid);
        Mail::assertNothingSent();
        DB::rollBack();
        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
        Mail::assertNothingSent();
    }
}
