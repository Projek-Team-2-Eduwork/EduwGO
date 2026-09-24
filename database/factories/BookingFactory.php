<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        $startDate = Carbon::now()->addDays($this->faker->numberBetween(1, 10));
        $duration = $this->faker->numberBetween(1, 7);
        $endDate = (clone $startDate)->addDays($duration);

        return [
            'code' => 'EG.'.str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'user_id' => User::factory(),
            'vehicle_id' => Vehicle::factory(), // Asumsi tabel vehicles ada
            'start_at' => $startDate,
            'end_at' => $endDate,
            'duration_days' => $duration,
            'price_per_day' => 100000,
            'total_amount' => 100000 * $duration,
            'status' => 'pending',
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
        ];
    }
}
