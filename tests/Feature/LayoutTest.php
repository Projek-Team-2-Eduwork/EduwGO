<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);
    }

    public function test_layout_user_menampilkan_navbar_dan_footer_tanpa_settings(): void
    {
        $html = Blade::render('<x-app-layout><p>konten</p></x-app-layout>');

        foreach (['EduwGo', 'konten', 'Pilih Kendaraan', 'Daftar Pesanan', 'Butuh bantuan?', 'Masuk', 'Socials', 'wa.me'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function test_navbar_user_login_menampilkan_avatar_bukan_tombol_masuk(): void
    {
        $user = User::factory()->create(['name' => 'Budi Santoso']);
        $user->assignRole('user');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Budi Santoso');
        $response->assertDontSee('Dashboard Admin');
    }

    public function test_admin_melihat_layout_admin_dengan_sidebar(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        foreach (['Dashboard', 'Daftar Pesanan', 'Kendaraan', 'Tipe Kendaraan', 'Pengaturan', 'Log Out'] as $menu) {
            $response->assertSee($menu);
        }
    }

    public function test_user_biasa_ditolak_di_halaman_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_guest_diarahkan_ke_login_dari_halaman_admin(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login', absolute: false));
    }

    public function test_guest_layout_tetap_menerima_slot_default(): void
    {
        $html = Blade::render('<x-guest-layout><p>isi form</p></x-guest-layout>');

        $this->assertStringContainsString('isi form', $html);
    }

    public function test_badge_status_memakai_label_enum(): void
    {
        $html = Blade::render('<x-badge-status status="paid" />');

        $this->assertStringContainsString('Lunas', $html);
    }

    public function test_badge_status_menolak_status_tidak_dikenal(): void
    {
        $this->expectException(ViewException::class);

        Blade::render('<x-badge-status status="tidak-ada" />');
    }
}
