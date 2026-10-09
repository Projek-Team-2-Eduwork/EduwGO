<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $terms = implode("\n", [
            'Penyewa wajib memiliki SIM yang masih berlaku dan sesuai dengan jenis kendaraan.',
            'Keterlambatan pengembalian melebihi batas waktu akan dikenakan denda sesuai tarif harian.',
            'Segala bentuk kerusakan atau kehilangan akibat kelalaian menjadi tanggung jawab penuh penyewa.',
            'Kendaraan tidak diperkenankan untuk dipindahtangankan atau disewakan kembali kepada pihak ketiga.',
            'Penggunaan kendaraan dibatasi hanya di dalam wilayah yang telah disepakati bersama.',
            'Kondisi bahan bakar saat pengembalian harus sama volumenya dengan saat awal pengambilan.',
        ]);

        $settings = [
            ['key' => 'brand.name', 'value' => 'EduwGo'],
            ['key' => 'contact.whatsapp', 'value' => '6281234567890'],
            ['key' => 'booking.max_days', 'value' => '5'],
            ['key' => 'booking.invoice_minutes', 'value' => '60'],
            ['key' => 'booking.buffer_minutes', 'value' => '60'],
            ['key' => 'content.terms', 'value' => $terms],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
