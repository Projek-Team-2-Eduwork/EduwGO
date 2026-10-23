<?php

namespace App\Events;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
        public BookingStatus $oldStatus,
        public BookingStatus $newStatus
    ) {}
}
