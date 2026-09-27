<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VehicleSeeder extends Seeder
{
    /**
     * Minimal 10 motor sesuai catatan tugas.
     * Path image mengikuti skema foto lokal (aset ditambahkan saat issue admin/kendaraan).
     */
    public function run(): void
    {
        $motors = [
            ['type' => 'Matic', 'name' => 'Honda Vario 160', 'plate' => 'B 1123 TYG', 'tank' => 8, 'price' => 120000],
            ['type' => 'Matic', 'name' => 'Yamaha NMAX 155', 'plate' => 'B 2245 UJH', 'tank' => 7, 'price' => 150000],
            ['type' => 'Matic', 'name' => 'Honda Beat 110', 'plate' => 'B 3356 KLM', 'tank' => 4, 'price' => 75000],
            ['type' => 'Matic', 'name' => 'Yamaha Fazzio 125', 'plate' => 'B 4467 RTV', 'tank' => 6, 'price' => 130000],
            ['type' => 'Matic', 'name' => 'Honda PCX 160', 'plate' => 'B 5578 WSA', 'tank' => 8, 'price' => 200000],
            ['type' => 'Matic', 'name' => 'Suzuki Address 110', 'plate' => 'B 6689 QPD', 'tank' => 5, 'price' => 90000],
            ['type' => 'Cub', 'name' => 'Honda Super Cub C125', 'plate' => 'B 7790 MNB', 'tank' => 5, 'price' => 140000],
            ['type' => 'Cub', 'name' => 'Yamaha XSR 155', 'plate' => 'B 8801 ZXC', 'tank' => 10, 'price' => 175000],
            ['type' => 'Sport', 'name' => 'Kawasaki W175', 'plate' => 'B 9912 KJH', 'tank' => 15, 'price' => 180000],
            ['type' => 'Sport', 'name' => 'Honda CB150R Streetfire', 'plate' => 'B 1023 GFD', 'tank' => 12, 'price' => 160000],
        ];

        foreach ($motors as $motor) {
            $type = VehicleType::query()->where('slug', Str::slug($motor['type']))->firstOrFail();

            Vehicle::query()->firstOrCreate(
                ['plate_number' => $motor['plate']],
                [
                    'vehicle_type_id' => $type->id,
                    'name' => $motor['name'],
                    'slug' => Str::slug($motor['name']),
                    'brand' => Str::before($motor['name'], ' '),
                    'tank_capacity' => $motor['tank'],
                    'price_per_day' => $motor['price'],
                    'image' => 'images/vehicles/'.Str::slug($motor['name']).'.jpg',
                    'description' => $motor['name'].' nyaman untuk harian maupun perjalanan jauh.',
                    'is_active' => true,
                ],
            );
        }
    }
}
