<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Seeder;

class BookingStatusHistorySeeder extends Seeder
{
    /**
     * Lengkapi jejak transisi status tiap booking
     * (BookingSeeder baru membuat 1 baris: status akhir).
     */
    public function run(): void
    {
        $trail = [
            'paid' => ['pending', 'paid'],
            'rented' => ['pending', 'paid', 'rented'],
            'returned' => ['pending', 'paid', 'rented', 'returned'],
            'expired' => ['pending', 'expired'],
            'cancelled' => ['pending', 'cancelled'],
        ];

        $bookings = Booking::query()->where('status', '!=', 'pending')->get();

        foreach ($bookings as $booking) {
            $current = $booking->status->value;
            $statuses = $trail[$current] ?? ['pending', $current];

            foreach ($statuses as $index => $status) {
                if ($booking->histories()->where('to_status', $status)->exists()) {
                    continue;
                }

                $booking->histories()->create([
                    'from_status' => $index === 0 ? null : $statuses[$index - 1],
                    'to_status' => $status,
                    'changed_by' => null,
                    'note' => 'Riwayat status (seeder)',
                ]);
            }
        }
    }
}
