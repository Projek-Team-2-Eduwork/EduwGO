<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Akun demo: 1 admin, 1 akun uji, dan penyewa demo untuk booking.
     * Password semua akun: "password" (dari UserFactory).
     */
    public function run(): void
    {
        $this->account('Admin EduwGo', 'admin@eduwgo.test', '081200000001', 'admin');
        $this->account('Test User', 'test@example.com', '081200000002', 'user');

        $renters = [
            ['Rani Putri Maharani', 'rani.putri@example.com', '081234560001', 'rani.putri'],
            ['Budi Santoso', 'budi.santoso@example.com', '081234560002', 'budisantoso'],
            ['Citra Lestari', 'citra.lestari@example.com', '081234560003', 'citralestari'],
            ['Dimas Prasetyo', 'dimas.prasetyo@example.com', '081234560004', 'dimas.pras'],
            ['Eka Wulandari', 'eka.wulandari@example.com', '081234560005', 'ekawulan'],
            ['Fajar Nugroho', 'fajar.nugroho@example.com', '081234560006', 'fajarngrh'],
        ];

        foreach ($renters as [$name, $email, $phone, $instagram]) {
            $this->account($name, $email, $phone, 'user', ['instagram' => $instagram]);
        }
    }

    private function account(string $name, string $email, string $phone, string $role, ?array $social = null): void
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        // forceFill: email_verified_at tidak masuk $fillable, password di-hash oleh cast.
        $user->forceFill([
            'name' => $name,
            'phone' => $phone,
            'social_account' => $social,
            'email_verified_at' => now(),
            'password' => 'password',
        ])->save();

        $user->syncRoles($role);
    }
}
