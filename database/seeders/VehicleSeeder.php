<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VehicleSeeder extends Seeder
{
    /**
     * 10 motor nyata. Foto lokal: public/images/vehicles/<slug>.jpg
     * (sumber & lisensi di public/images/vehicles/CREDITS.md).
     * tank_capacity kolom integer (liter), nilai spesifikasi dibulatkan.
     */
    public function run(): void
    {
        $motors = [
            [
                'type' => 'Matic', 'name' => 'Honda Vario 125', 'plate' => 'B 3421 KJR', 'tank' => 6, 'price' => 85000,
                'description' => 'Skuter matic 125 cc yang irit dan lincah untuk aktivitas harian di dalam kota. Jok lega dan bagasi luas cukup untuk helm serta tas kuliah atau kerja.',
            ],
            [
                'type' => 'Matic', 'name' => 'Honda Vario 160', 'plate' => 'B 4187 TFM', 'tank' => 6, 'price' => 110000,
                'description' => 'Versi 160 cc dengan tenaga lebih besar, nyaman untuk jarak menengah dan jalan yang menanjak. Dilengkapi rem ABS dan lampu LED yang terang.',
            ],
            [
                'type' => 'Matic', 'name' => 'Honda Scoopy', 'plate' => 'B 5632 UDA', 'tank' => 4, 'price' => 80000,
                'description' => 'Skuter bergaya klasik yang mungil dan mudah dikendalikan, cocok untuk pemula. Posisi duduk rendah membuat kaki mudah menapak ke tanah.',
            ],
            [
                'type' => 'Matic', 'name' => 'Honda Genio', 'plate' => 'B 6274 SQW', 'tank' => 4, 'price' => 70000,
                'description' => 'Matic bergaya retro modern dengan bodi ringan dan konsumsi bahan bakar hemat. Pilihan pas untuk keliling kota dengan anggaran terbatas.',
            ],
            [
                'type' => 'Matic', 'name' => 'Honda BeAT', 'plate' => 'B 2918 NRK', 'tank' => 4, 'price' => 65000,
                'description' => 'Matic paling ringan dan irit di kelasnya, favorit untuk mobilitas harian. Mudah dibawa menembus kemacetan dan parkir di ruang sempit.',
            ],
            [
                'type' => 'Matic', 'name' => 'Honda PCX 160', 'plate' => 'B 7345 PXC', 'tank' => 8, 'price' => 140000,
                'description' => 'Skuter premium dengan mesin 160 cc yang halus dan suspensi empuk untuk perjalanan jauh. Fitur keyless dan bagasi luas membuat perjalanan lebih praktis.',
            ],
            [
                'type' => 'Matic', 'name' => 'Honda ADV 160', 'plate' => 'B 8116 GHT', 'tank' => 8, 'price' => 150000,
                'description' => 'Skuter petualang berpostur tinggi dengan ban besar yang tangguh di jalan rusak. Tampilan sporty dan nyaman untuk touring santai akhir pekan.',
            ],
            [
                'type' => 'Matic', 'name' => 'Honda Forza', 'plate' => 'B 9052 FRZ', 'tank' => 12, 'price' => 150000,
                'description' => 'Maxi skuter bermesin besar dengan windshield dan jok empuk untuk berkendara jarak jauh. Tenaga responsif, stabil di kecepatan tinggi.',
            ],
            [
                'type' => 'Matic', 'name' => 'Honda Stylo 160', 'plate' => 'B 1764 STL', 'tank' => 6, 'price' => 105000,
                'description' => 'Skuter neo-retro bermesin 160 cc dengan jok dua warna yang khas. Nyaman untuk bergaya di dalam kota sekaligus cukup bertenaga untuk luar kota.',
            ],
            [
                'type' => 'Cub', 'name' => 'Honda Supra X 125', 'plate' => 'B 3809 SPX', 'tank' => 4, 'price' => 60000,
                'description' => 'Motor bebek bertransmisi manual yang terkenal awet dan irit bahan bakar. Cocok untuk penyewa yang terbiasa mengoper gigi dan butuh biaya sewa terjangkau.',
            ],
        ];

        foreach ($motors as $motor) {
            $slug = Str::slug($motor['name']);

            Vehicle::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'vehicle_type_id' => VehicleType::query()->where('slug', Str::slug($motor['type']))->firstOrFail()->id,
                    'name' => $motor['name'],
                    'brand' => Str::before($motor['name'], ' '),
                    'plate_number' => $motor['plate'],
                    'tank_capacity' => $motor['tank'],
                    'price_per_day' => $motor['price'],
                    'image' => 'images/vehicles/'.$slug.'.jpg',
                    'description' => $motor['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}
