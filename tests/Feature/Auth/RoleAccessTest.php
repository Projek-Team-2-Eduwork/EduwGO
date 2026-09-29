<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);
    }

    public function test_admin_login_diarahkan_ke_dashboard_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_user_biasa_akses_admin_ditolak_403(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_registrasi_otomatis_assign_role_user(): void
    {
        $response = $this->post('/register', [
            'name' => 'Penyewa Baru',
            'email' => 'penyewa@example.com',
            'phone' => '081234567890',
            'terms' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('verification.notice', absolute: false));
        $this->assertTrue(User::where('email', 'penyewa@example.com')->first()->hasRole('user'));
    }

    public function test_user_belum_verifikasi_diarahkan_ke_verify_email_di_route_verified(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('user');

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('verification.notice', absolute: false));

        $this->actingAs($user)->get('/pesanan/EG.000001/menunggu')
            ->assertRedirect(route('verification.notice', absolute: false));
    }
}
