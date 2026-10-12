<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company().' Outlet',
            'address' => $this->faker->address(),
            'phone' => '08'.$this->faker->unique()->numberBetween(1000000000, 9999999999),
            'operating_hours' => 'Setiap hari, 08.00 – 20.00 WIB',
            'is_active' => true,
            // slug diisi model (booted) saat creating
        ];
    }
}
