<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'paid', 'expired', 'failed']);
        $reference = 'inv_'.$this->faker->unique()->numerify('##########');

        return [
            'booking_id' => Booking::factory(),
            'method' => 'xendit_invoice',
            'gateway_reference' => $status === 'pending' ? null : $reference,
            'gateway_url' => 'https://checkout.xendit.co/'.$reference,
            'gateway_payload' => ['id' => $reference, 'status' => strtoupper($status)],
            'amount' => $this->faker->numberBetween(1, 7) * 100000,
            'status' => $status,
            'paid_at' => $status === 'paid' ? $this->faker->dateTimeBetween('-3 days') : null,
            'expires_at' => $this->faker->dateTimeBetween('+1 hour', '+24 hours'),
        ];
    }
}
