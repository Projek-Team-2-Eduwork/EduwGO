<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            VehicleTypeSeeder::class,
            VehicleSeeder::class,
            BookingSeeder::class,
            BookingStatusHistorySeeder::class,
            PaymentSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Admin EduwGo',
            'email' => 'admin@eduwgo.test',
        ])->assignRole('admin');

        User::factory(2)->create()->each(
            fn (User $user) => $user->assignRole('user'),
        );

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assignRole('user');
    }
}
