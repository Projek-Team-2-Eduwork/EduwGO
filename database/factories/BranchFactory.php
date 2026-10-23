<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company().' Outlet',
            'address' => $this->faker->address(),
            'phone' => '08'.$this->faker->unique()->numberBetween(1000000000, 9999999999),
            'operating_hours' => 'Setiap hari, 08.00 – 20.00 WIB',
            'social_links' => null,
            'is_active' => true,
        ];
    }
}
