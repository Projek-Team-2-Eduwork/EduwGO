<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingStatusHistory>
 */
class BookingStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $toStatus = $this->faker->randomElement([
            'pending', 'paid', 'rented', 'returned', 'cancelled', 'expired',
        ]);

        return [
            'booking_id' => Booking::factory(),
            'from_status' => $toStatus === 'pending' ? null : 'pending',
            'to_status' => $toStatus,
            'changed_by' => null, // null = sistem (webhook/cron)
            'note' => $this->faker->sentence(),
        ];
    }
}
