<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function staffRoles(): array
    {
        return [['superadmin'], ['admin']];
    }

    private function staff(string $role): User
    {
        $this->seed(RoleSeeder::class);

        $staff = User::factory()->create([
            'branch_id' => $role === 'admin' ? Branch::factory()->create()->id : null,
        ]);
        $staff->assignRole($role);

        return $staff;
    }

    #[DataProvider('staffRoles')]
    public function test_login_pertama_staf_langsung_membuka_dashboard_admin(string $role): void
    {
        $staff = $this->staff($role);

        $this->post('/login', ['email' => $staff->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))->assertOk()->assertViewIs('admin.dashboard');
    }

    #[DataProvider('staffRoles')]
    public function test_login_staf_mengabaikan_tujuan_halaman_pengguna(string $role): void
    {
        $staff = $this->staff($role);

        $this->withSession(['url.intended' => url('/kendaraan')])
            ->post('/login', ['email' => $staff->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    #[DataProvider('staffRoles')]
    public function test_dashboard_umum_mengarahkan_staf_ke_dashboard_admin(string $role): void
    {
        $this->actingAs($this->staff($role))->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    #[DataProvider('staffRoles')]
    public function test_staf_yang_sudah_login_diarahkan_ke_dashboard_admin_dari_halaman_login(string $role): void
    {
        $this->actingAs($this->staff($role))->get('/login')
            ->assertRedirect(route('admin.dashboard'));
    }
}
