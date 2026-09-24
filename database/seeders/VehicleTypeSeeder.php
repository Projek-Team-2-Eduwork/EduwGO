<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Tipe motor dasar sesuai desain (Matic, Cub, Sport, dst).
     */
    public function run(): void
    {
        foreach (['Matic', 'Cub', 'Sport', 'Trail'] as $name) {
            VehicleType::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }
}
