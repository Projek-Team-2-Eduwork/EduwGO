<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CabangTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_tampil_untuk_superadmin()
    {
        $this->actingAsSuperadmin();

        $this->get(route('admin.cabang.index'))->assertOk()->assertSee('Kelola Cabang');
    }

    public function test_create_cabang_berhasil_dengan_slug_otomatis_dan_sosmed()
    {
        $this->actingAsSuperadmin();

        $response = $this->post(route('admin.cabang.store'), [
            'name' => 'Cabang Malang Kota',
            'address' => 'Jl. Ijen No. 1, Malang',
            'phone' => '08111111111',
            'operating_hours' => 'Setiap hari, 08.00 – 20.00 WIB',
            'social_links' => ['instagram' => 'https://instagram.com/malang', 'facebook' => ''],
        ]);

        $response->assertSessionHas('success');
        $branch = Branch::where('slug', 'cabang-malang-kota')->first();
        $this->assertNotNull($branch);
        $this->assertSame(['instagram' => 'https://instagram.com/malang'], $branch->social_links);
    }

    public function test_cabang_baru_otomatis_muncul_di_tentang_dan_footer()
    {
        $this->actingAsSuperadmin();
        $this->post(route('admin.cabang.store'), [
            'name' => 'Cabang Baru Sekali',
            'address' => 'Jl. Anyar No. 9',
            'operating_hours' => 'Setiap hari, 10.00 – 18.00 WIB',
            'phone' => '08122223333',
        ]);

        $this->get('/tentang')
            ->assertOk()
            ->assertSee('Cabang Baru Sekali')
            ->assertSee('Jl. Anyar No. 9')
            ->assertSee('Setiap hari, 10.00 – 18.00 WIB')
            ->assertSee('https://wa.me/628122223333', false);
    }

    public function test_update_nama_berhasil_tapi_slug_tetap()
    {
        $cabang = Branch::factory()->create(['name' => 'Cabang Utama', 'slug' => 'cabang-utama-x']);
        $this->actingAsSuperadmin();

        $this->put(route('admin.cabang.update', $cabang), ['name' => 'Cabang Baru']);

        $this->assertDatabaseHas('branches', ['id' => $cabang->id, 'name' => 'Cabang Baru', 'slug' => 'cabang-utama-x']);
    }

    public function test_slug_duplikat_ditolak()
    {
        Branch::factory()->create(['name' => 'Cabang Utama', 'slug' => 'cabang-utama']);
        $this->actingAsSuperadmin();

        $this->post(route('admin.cabang.store'), ['name' => 'Cabang Utama'])->assertSessionHasErrors('name');
        $this->assertSame(1, Branch::count());
    }

    public function test_validasi_nama_kosong_dan_sosmed_bukan_url_ditolak()
    {
        $this->actingAsSuperadmin();

        $this->post(route('admin.cabang.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->post(route('admin.cabang.store'), ['name' => 'X', 'social_links' => ['instagram' => 'bukan url']])
            ->assertSessionHasErrors('social_links.instagram');
    }

    public function test_hapus_cabang_tanpa_unit_dan_admin_berhasil()
    {
        $cabang = Branch::factory()->create();
        $this->actingAsSuperadmin();

        $this->delete(route('admin.cabang.destroy', $cabang));

        $this->assertDatabaseMissing('branches', ['id' => $cabang->id]);
    }

    public function test_hapus_cabang_dengan_kendaraan_atau_admin_ditolak()
    {
        $denganUnit = Branch::factory()->create();
        Vehicle::factory()->create(['branch_id' => $denganUnit->id]);
        $denganAdmin = Branch::factory()->create();
        User::factory()->create(['branch_id' => $denganAdmin->id]);
        $this->actingAsSuperadmin();

        $this->delete(route('admin.cabang.destroy', $denganUnit))->assertSessionHas('error');
        $this->delete(route('admin.cabang.destroy', $denganAdmin))->assertSessionHas('error');
        $this->assertDatabaseHas('branches', ['id' => $denganUnit->id]);
        $this->assertDatabaseHas('branches', ['id' => $denganAdmin->id]);
    }

    public function test_admin_cabang_user_biasa_dan_guest_ditolak()
    {
        $this->actingAsAdmin();
        $this->get(route('admin.cabang.index'))->assertForbidden();
        $this->post(route('admin.cabang.store'), ['name' => 'Cabang Gelap'])->assertForbidden();
        $this->assertDatabaseMissing('branches', ['name' => 'Cabang Gelap']);

        $this->actingAs(User::factory()->create())->get(route('admin.cabang.index'))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.cabang.index'))->assertRedirect(route('login'));
    }
}
