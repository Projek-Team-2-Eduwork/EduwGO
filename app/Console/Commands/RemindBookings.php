<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Mail\BookingReminderMail;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class RemindBookings extends Command
{
    protected $signature = 'bookings:remind';

    protected $description = 'Kirim email pengingat H-1 untuk pengambilan dan pengembalian kendaraan.';

    public function handle(): int
    {
        $besok = Carbon::tomorrow();

        // Pengingat Pengambilan (Pickup)
        $pickups = Booking::where('status', BookingStatus::Paid)
            ->whereDate('start_at', $besok)
            ->with(['user', 'vehicle'])
            ->get();

        foreach ($pickups as $booking) {
            Mail::to($booking->user->email)->send(new BookingReminderMail($booking, 'pickup'));
        }

        // Pengingat Pengembalian (Return)
        $returns = Booking::where('status', BookingStatus::Rented)
            ->whereDate('end_at', $besok)
            ->with(['user', 'vehicle'])
            ->get();

        foreach ($returns as $booking) {
            Mail::to($booking->user->email)->send(new BookingReminderMail($booking, 'return'));
        }

        $this->info(sprintf('Terkirim %d pengingat pengambilan dan %d pengingat pengembalian.', $pickups->count(), $returns->count()));

        return Command::SUCCESS;
    }
}
