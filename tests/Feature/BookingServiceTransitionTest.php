<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Events\BookingStatusChanged;
use App\Exceptions\InvalidTransitionException;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tests\Unit\BookingStatusTest;

class BookingServiceTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BookingService;
    }

    /** Provider delegasi — daftar lengkap transisi didefinisikan di BookingStatusTest. */
    public static function validTransitions(): array
    {
        return BookingStatusTest::validTransitions();
    }

    public static function invalidTransitions(): array
    {
        return BookingStatusTest::invalidTransitions();
    }

    public function test_valid_transition_updates_booking_and_creates_history()
    {
        Event::fake();

        $booking = Booking::factory()->create([
            'status' => BookingStatus::Pending,
        ]);
        $user = User::factory()->create();
        $note = 'Pembayaran via transfer bank';

        $updatedBooking = $this->service->transition($booking, BookingStatus::Paid, $user, $note);

        // Verifikasi Update Status
        $this->assertEquals(BookingStatus::Paid, $updatedBooking->status);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'paid',
        ]);

        // Verifikasi History Terbuat
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'from_status' => 'pending',
            'to_status' => 'paid',
            'changed_by' => $user->id,
            'note' => $note,
        ]);

        // Verifikasi Event
        Event::assertDispatched(BookingStatusChanged::class, function ($event) use ($booking) {
            return $event->booking->id === $booking->id &&
                   $event->oldStatus === BookingStatus::Pending &&
                   $event->newStatus === BookingStatus::Paid;
        });
    }

    public function test_invalid_transition_throws_exception()
    {
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Returned, // Final status
        ]);

        $this->expectException(InvalidTransitionException::class);
        $this->expectExceptionMessage("Tidak dapat melakukan transisi status dari 'returned' ke 'pending'.");

        $this->service->transition($booking, BookingStatus::Pending);
    }

    #[DataProvider('validTransitions')]
    public function test_all_valid_transitions_succeed_and_record_history(BookingStatus $from, BookingStatus $to): void
    {
        $booking = Booking::factory()->create(['status' => $from]);

        $updated = $this->service->transition($booking, $to, null, 'Diuji otomatis (data provider)');

        $this->assertSame($to, $updated->status);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => $to->value,
        ]);
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'changed_by' => null,
            'note' => 'Diuji otomatis (data provider)',
        ]);
    }

    #[DataProvider('invalidTransitions')]
    public function test_all_invalid_transitions_throw(BookingStatus $from, BookingStatus $to): void
    {
        $booking = Booking::factory()->create(['status' => $from]);

        $this->expectException(InvalidTransitionException::class);
        $this->expectExceptionMessage(
            "Tidak dapat melakukan transisi status dari '{$from->value}' ke '{$to->value}'."
        );

        $this->service->transition($booking, $to);
    }
}
