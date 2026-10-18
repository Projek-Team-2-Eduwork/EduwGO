<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'slug' => 'cabang-malang-kota',
                'name' => 'Cabang Malang Kota',
                'address' => 'Jl. Ijen No. 12, Klojen, Malang',
                'phone' => '081234567801',
                'operating_hours' => 'Setiap hari, 08.00 – 20.00 WIB',
                'social_links' => [
                    'instagram' => 'https://instagram.com/eduwgo.malang',
                    'facebook' => 'https://facebook.com/eduwgo.malang',
                    'tiktok' => 'https://tiktok.com/@eduwgo.malang',
                ],
            ],
            [
                'slug' => 'cabang-batu',
                'name' => 'Cabang Batu',
                'address' => 'Jl. Diponegoro No. 5, Batu',
                'phone' => '081234567802',
                'operating_hours' => 'Setiap hari, 07.00 – 21.00 WIB',
                'social_links' => [
                    'instagram' => 'https://instagram.com/eduwgo.batu',
                    'youtube' => 'https://youtube.com/@eduwgobatu',
                ],
            ],
            [
                'slug' => 'cabang-surabaya',
                'name' => 'Cabang Surabaya',
                'address' => 'Jl. Raya Darmo No. 88, Surabaya',
                'phone' => '081234567803',
                'operating_hours' => 'Senin – Sabtu, 08.00 – 19.00 WIB',
                'social_links' => [
                    'instagram' => 'https://instagram.com/eduwgo.surabaya',
                    'facebook' => 'https://facebook.com/eduwgo.surabaya',
                ],
            ],
        ];

        foreach ($branches as $branch) {
            Branch::query()->updateOrCreate(['slug' => $branch['slug']], $branch + ['is_active' => true]);
        }
    }
}
