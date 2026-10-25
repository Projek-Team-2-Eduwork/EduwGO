<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user']);
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_halaman_tampil_dengan_data_dan_jumlah_booking()
    {
        $admin = $this->actingAsSuperadmin();
        $user = User::factory()->create(['name' => 'Sutrisno']);
        Booking::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->get(route('admin.pengguna.index', ['tab' => 'penyewa']));
        $response->assertOk();
        $response->assertSee('Sutrisno');
        $response->assertSee('3'); // Jumlah booking via withCount
    }

    public function test_pencarian_memfilter_pengguna()
    {
        $admin = $this->actingAsSuperadmin();
        User::factory()->create(['name' => 'Joko', 'email' => 'joko@gmail.com']);
        User::factory()->create(['name' => 'Siti']);

        $response = $this->get(route('admin.pengguna.index', ['tab' => 'penyewa', 'q' => 'joko']));
        $response->assertSee('Joko');
        $response->assertDontSee('Siti');
    }

    public function test_superadmin_bisa_memindah_cabang_admin()
    {
        $this->actingAsSuperadmin();
        $cabang = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => Branch::factory()->create()->id]);
        $user->assignRole('admin');

        $this->post(route('admin.pengguna.role', $user->id), ['role' => 'admin', 'branch_id' => $cabang->id]);

        $user->refresh();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertSame($cabang->id, $user->branch_id);
    }

    public function test_role_penyewa_tidak_bisa_diubah_lewat_panel()
    {
        $this->actingAsSuperadmin();
        $cabang = Branch::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->post(route('admin.pengguna.role', $user->id), ['role' => 'admin', 'branch_id' => $cabang->id])->assertForbidden();
        $this->assertFalse($user->fresh()->hasRole('admin'));
    }

    public function test_superadmin_membuat_admin_cabang_baru_langsung()
    {
        $this->actingAsSuperadmin();
        $cabang = Branch::factory()->create();

        $this->post(route('admin.pengguna.store'), [
            'name' => 'Admin Baru',
            'email' => 'baru@eduwgo.test',
            'phone' => '08123456789',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'branch_id' => $cabang->id,
        ])->assertSessionHas('success');

        $admin = User::where('email', 'baru@eduwgo.test')->first();
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertSame($cabang->id, $admin->branch_id);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('rahasia123', $admin->password));
    }

    public function test_buat_admin_ditolak_bila_email_dobel_cabang_kosong_atau_password_lemah()
    {
        $this->actingAsSuperadmin();
        $cabang = Branch::factory()->create();
        User::factory()->create(['email' => 'ada@eduwgo.test']);
        $base = ['name' => 'X', 'email' => 'x@eduwgo.test', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'branch_id' => $cabang->id];

        $this->post(route('admin.pengguna.store'), ['email' => 'ada@eduwgo.test'] + $base)->assertSessionHasErrors('email');
        $this->post(route('admin.pengguna.store'), ['branch_id' => ''] + $base)->assertSessionHasErrors('branch_id');
        $this->post(route('admin.pengguna.store'), ['password' => 'pendek', 'password_confirmation' => 'pendek'] + $base)->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'x@eduwgo.test']);
    }

    public function test_admin_cabang_tidak_bisa_membuat_admin_baru()
    {
        $this->actingAsAdmin();

        $this->post(route('admin.pengguna.store'), ['name' => 'X', 'email' => 'x@eduwgo.test', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'branch_id' => 1])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'x@eduwgo.test']);
    }

    public function test_role_admin_tanpa_cabang_ditolak()
    {
        $this->actingAsSuperadmin();
        Role::firstOrCreate(['name' => 'superadmin']);
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        $this->post(route('admin.pengguna.role', $user->id), ['role' => 'admin'])->assertSessionHasErrors('branch_id');
        $this->assertFalse($user->fresh()->hasRole('admin'));
    }

    public function test_mengubah_admin_jadi_penyewa_melepas_cabang()
    {
        $this->actingAsSuperadmin();
        $admin = User::factory()->create(['branch_id' => Branch::factory()->create()->id]);
        $admin->assignRole('admin');

        $this->post(route('admin.pengguna.role', $admin->id), ['role' => 'user']);

        $admin->refresh();
        $this->assertTrue($admin->hasRole('user'));
        $this->assertNull($admin->branch_id);
    }

    public function test_superadmin_tidak_bisa_mengubah_role_sendiri()
    {
        $superadmin = $this->actingAsSuperadmin();

        $response = $this->post(route('admin.pengguna.role', $superadmin->id), ['role' => 'user']);
        $response->assertForbidden();
        $this->assertTrue($superadmin->fresh()->hasRole('superadmin'));
    }

    public function test_admin_cabang_tidak_bisa_kelola_pengguna()
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->get(route('admin.pengguna.index'))->assertForbidden();
        $this->post(route('admin.pengguna.role', $user->id), ['role' => 'superadmin'])->assertForbidden();
        $this->assertFalse($user->fresh()->hasRole('superadmin'));
    }

    public function test_superadmin_bisa_toggle_active_pengguna()
    {
        $admin = $this->actingAsSuperadmin();
        $user = User::factory()->create(['is_active' => true]);

        $this->post(route('admin.pengguna.toggle', $user->id));
        $this->assertFalse((bool) $user->fresh()->is_active);
    }

    public function test_akun_nonaktif_tidak_bisa_login_dan_sesi_terkick()
    {
        $user = User::factory()->create(['is_active' => false]);

        // Coba Login (via web middleware / controller auth)
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // Coba pakai sesi yang sudah terauth, tapi ditendang oleh middleware
        $responseKick = $this->actingAs($user)->get(route('dashboard'));
        $responseKick->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_non_admin_dan_guest_ditolak()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.pengguna.index'))->assertForbidden();
        auth()->logout();
        $this->get(route('admin.pengguna.index'))->assertRedirect(route('login'));
    }
}
