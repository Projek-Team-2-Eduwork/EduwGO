<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'user']);
    }

    public function test_halaman_tampil_dengan_data_dan_jumlah_booking()
    {
        $admin = $this->actingAsAdmin();
        $user = User::factory()->create(['name' => 'Sutrisno']);
        Booking::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->get(route('admin.pengguna.index'));
        $response->assertOk();
        $response->assertSee('Sutrisno');
        $response->assertSee('3'); // Jumlah booking via withCount
    }

    public function test_pencarian_memfilter_pengguna()
    {
        $admin = $this->actingAsAdmin();
        User::factory()->create(['name' => 'Joko', 'email' => 'joko@gmail.com']);
        User::factory()->create(['name' => 'Siti']);

        $response = $this->get(route('admin.pengguna.index', ['q' => 'joko']));
        $response->assertSee('Joko');
        $response->assertDontSee('Siti');
    }

    public function test_admin_bisa_mengubah_role_pengguna()
    {
        $admin = $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->post(route('admin.pengguna.role', $user->id), ['role' => 'admin']);
        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function test_admin_tidak_bisa_mencabut_role_sendiri()
    {
        $admin = $this->actingAsAdmin();

        $response = $this->post(route('admin.pengguna.role', $admin->id), ['role' => 'user']);
        $response->assertForbidden();
        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_admin_bisa_toggle_active_pengguna()
    {
        $admin = $this->actingAsAdmin();
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
