<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Honda Vario 160',
            'Yamaha NMAX 155',
            'Honda Beat 110',
            'Yamaha Fazzio 125',
            'Honda PCX 160',
            'Suzuki Address 110',
            'Kawasaki W175',
            'Yamaha XSR 155',
            'Honda CB150R',
            'Kawasaki KLX 150',
        ]);

        // Tipe motor memakai slug yang sama dengan VehicleTypeSeeder (firstOrCreate
        // di closure) supaya factory dan seeder tidak bentrok unique constraint.
        $type = $this->faker->randomElement(['Matic', 'Cub', 'Sport', 'Trail']);

        return [
            'vehicle_type_id' => fn () => VehicleType::query()->firstOrCreate(
                ['slug' => Str::slug($type)],
                ['name' => $type],
            )->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.strtolower($this->faker->unique()->bothify('##??')),
            'brand' => Str::before($name, ' '),
            'plate_number' => strtoupper($this->faker->unique()->bothify('?? #### ??')),
            'tank_capacity' => $this->faker->numberBetween(4, 15),
            'price_per_day' => $this->faker->randomElement([75000, 100000, 120000, 150000, 200000]),
            'image' => 'images/vehicles/'.Str::slug($name).'.jpg',
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
