<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Akun demo: 1 superadmin, admin per cabang, 1 akun uji, dan penyewa demo untuk booking.
     * Password semua akun: "password" (dari UserFactory). BranchSeeder harus jalan lebih dulu.
     */
    public function run(): void
    {
        $this->account('Superadmin EduwGo', 'superadmin@eduwgo.test', '081200000010', RoleSeeder::SUPERADMIN);
        $this->account('Admin EduwGo', 'admin@eduwgo.test', '081200000001', RoleSeeder::ADMIN, null, 'cabang-malang-kota');
        $this->account('Admin Cabang Batu', 'admin.batu@eduwgo.test', '081200000011', RoleSeeder::ADMIN, null, 'cabang-batu');
        $this->account('Admin Cabang Surabaya', 'admin.surabaya@eduwgo.test', '081200000012', RoleSeeder::ADMIN, null, 'cabang-surabaya');
        $this->account('Test User', 'test@example.com', '081200000002', RoleSeeder::USER);

        $renters = [
            ['Rani Putri Maharani', 'rani.putri@example.com', '081234560001', 'rani.putri'],
            ['Budi Santoso', 'budi.santoso@example.com', '081234560002', 'budisantoso'],
            ['Citra Lestari', 'citra.lestari@example.com', '081234560003', 'citralestari'],
            ['Dimas Prasetyo', 'dimas.prasetyo@example.com', '081234560004', 'dimas.pras'],
            ['Eka Wulandari', 'eka.wulandari@example.com', '081234560005', 'ekawulan'],
            ['Fajar Nugroho', 'fajar.nugroho@example.com', '081234560006', 'fajarngrh'],
        ];

        foreach ($renters as [$name, $email, $phone, $instagram]) {
            $this->account($name, $email, $phone, RoleSeeder::USER, ['instagram' => $instagram]);
        }
    }

    private function account(string $name, string $email, string $phone, string $role, ?array $social = null, ?string $branchSlug = null): void
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        // forceFill: email_verified_at tidak masuk $fillable, password di-hash oleh cast.
        $user->forceFill([
            'name' => $name,
            'phone' => $phone,
            'social_account' => $social,
            'email_verified_at' => now(),
            'password' => 'password',
            'branch_id' => $branchSlug ? Branch::query()->where('slug', $branchSlug)->value('id') : null,
        ])->save();

        $user->syncRoles($role);
    }
}
