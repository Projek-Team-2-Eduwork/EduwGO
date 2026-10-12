<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class LokasiSeeder extends Seeder
{
    /**
     * Seed master lokasi + backfill unit existing tanpa lokasi.
     * Catatan: slug diisi eksplisit karena DatabaseSeeder memakai
     * WithoutModelEvents (event creating model tidak berjalan saat seeding).
     */
    public function run(): void
    {
        $lokasi = Location::query()->firstOrCreate(
            ['name' => 'Lokasi Utama'],
            [
                'slug' => 'lokasi-utama',
                'address' => 'Jl. Raya Kota No. 1, Malang',
                'phone' => '081234567890',
                'operating_hours' => 'Setiap hari, 08.00 – 20.00 WIB',
                'is_active' => true,
            ],
        );

        // Backfill: semua unit existing yang belum punya lokasi menunjuk ke Lokasi Utama
        Vehicle::query()->whereNull('location_id')->update(['location_id' => $lokasi->id]);
    }
}
