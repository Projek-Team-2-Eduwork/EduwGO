<?php

namespace App\Listeners;

use App\Enums\BookingStatus;
use App\Events\BookingStatusChanged;
use App\Mail\BookingStatusMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendBookingStatusEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(BookingStatusChanged $event): void
    {
        $booking = $event->booking;

        $subject = match ($event->newStatus) {
            BookingStatus::Pending => 'Selesaikan pembayaran',
            BookingStatus::Paid => 'Pembayaran diterima',
            BookingStatus::Expired => 'Waktu habis',
            BookingStatus::Cancelled => 'Booking dibatalkan',
            default => null,
        };

        if ($subject) {
            Mail::to($booking->user->email)->send(new BookingStatusMail($booking, $subject));
        }
    }
}
