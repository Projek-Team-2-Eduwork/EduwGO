<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed role superadmin (semua cabang), admin (admin cabang), dan user (penyewa).
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'superadmin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);
    }
}
