<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'EG.'.$this->faker->unique()->numerify('######'),
            'user_id' => User::factory(),
            'vehicle_id' => Vehicle::factory(),
            'start_at' => now(),
            'end_at' => now()->addDays(1),
            'duration_days' => 1,
            'price_per_day' => 100000,
            'total_amount' => 100000,
            'status' => 'pending',
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => 'paid']);
    }

    public function rented(): static
    {
        return $this->state(fn () => ['status' => 'rented']);
    }

    public function returned(): static
    {
        return $this->state(fn () => ['status' => 'returned']);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['status' => 'expired']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
