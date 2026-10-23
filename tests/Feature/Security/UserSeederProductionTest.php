<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

/**
 * UserSeeder tidak boleh membuat superadmin/admin berpassword "password" di production.
 */
class UserSeederProductionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, BranchSeeder::class]);
    }

    /**
     * Dijalankan langsung (bukan lewat artisan db:seed) karena di production
     * perintah artisan meminta konfirmasi interaktif.
     */
    private function runUserSeeder(): void
    {
        $this->app->make(UserSeeder::class)->setContainer($this->app)->run();
    }

    public function test_lokal_tanpa_env_tetap_memakai_password_demo(): void
    {
        $this->runUserSeeder();

        $this->assertTrue(Hash::check('password', User::where('email', 'superadmin@eduwgo.test')->value('password')));
    }

    public function test_production_tanpa_seed_user_password_menolak_membuat_akun(): void
    {
        $this->app['env'] = 'production';
        config(['app.seed_user_password' => null]);

        try {
            $this->runUserSeeder();
            $this->fail('UserSeeder seharusnya menolak berjalan di production tanpa SEED_USER_PASSWORD.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('SEED_USER_PASSWORD', $e->getMessage());
        }

        $this->assertSame(0, User::count());
    }

    public function test_production_menolak_password_pendek(): void
    {
        $this->app['env'] = 'production';
        config(['app.seed_user_password' => 'password']);

        $this->expectException(RuntimeException::class);

        $this->runUserSeeder();
    }

    public function test_production_memakai_password_dari_env(): void
    {
        $this->app['env'] = 'production';
        config(['app.seed_user_password' => 'rahasia-panjang-123']);

        $this->runUserSeeder();

        $hash = User::where('email', 'superadmin@eduwgo.test')->value('password');
        $this->assertTrue(Hash::check('rahasia-panjang-123', $hash));
        $this->assertFalse(Hash::check('password', $hash));
    }
}
