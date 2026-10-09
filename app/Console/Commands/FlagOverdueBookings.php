<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class FlagOverdueBookings extends Command
{
    protected $signature = 'bookings:flag-overdue';
    protected $description = 'Menandai pesanan yang terlambat dikembalikan ke dalam riwayat';

    public function handle()
    {
        $bookings = Booking::overdue()
            ->whereDoesntHave('histories', fn ($q) => $q->where('note', 'Terlambat dikembalikan'))
            ->get();

        $count = 0;
        foreach ($bookings as $booking) {
            $booking->histories()->create([
                'from_status' => 'rented',
                'to_status'   => 'rented',
                'changed_by'  => null,
                'note'        => 'Terlambat dikembalikan',
            ]);
            $count++;
        }

        $this->info("Berhasil menandai {$count} pesanan terlambat.");
        
        return Command::SUCCESS;
    }
}