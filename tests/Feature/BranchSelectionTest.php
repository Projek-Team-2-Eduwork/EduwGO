<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BranchSelectionTest extends TestCase
{
    use RefreshDatabase;

    private Branch $malang;

    private Branch $batu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->malang = Branch::factory()->create(['name' => 'Cabang Malang', 'slug' => 'malang', 'address' => 'Jl. Ijen 1', 'operating_hours' => 'Jam Malang']);
        $this->batu = Branch::factory()->create(['name' => 'Cabang Batu', 'slug' => 'batu', 'address' => 'Jl. Panglima 2', 'operating_hours' => 'Jam Batu']);

        Vehicle::factory()->create(['branch_id' => $this->malang->id, 'name' => 'Motor Malang']);
        Vehicle::factory()->create(['branch_id' => $this->batu->id, 'name' => 'Motor Batu']);
    }

    public function test_home_menampilkan_motor_cabang_terpilih_saja(): void
    {
        $this->get('/')->assertOk()->assertSee('Motor Batu')->assertDontSee('Motor Malang');

        $this->get('/?cabang=malang')->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('Motor Malang')->assertDontSee('Motor Batu');
    }

    public function test_pilihan_cabang_tersimpan_di_cookie_untuk_kunjungan_berikutnya(): void
    {
        $this->withCookie('branch', 'malang')
            ->get('/kendaraan')
            ->assertOk()
            ->assertSee('Motor Malang')
            ->assertDontSee('Motor Batu');
    }

    public function test_pemilih_cabang_tampil_di_navbar_dengan_semua_cabang_aktif(): void
    {
        Branch::factory()->create(['name' => 'Cabang Tutup', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Pilih cabang')
            ->assertSee('Cabang Malang')
            ->assertSee('Cabang Batu')
            ->assertDontSee('Cabang Tutup');
    }

    public function test_detail_motor_cabang_lain_memindahkan_cabang_terpilih_dan_rekomendasi_satu_cabang(): void
    {
        $unit = Vehicle::where('name', 'Motor Malang')->first();
        Vehicle::factory()->create(['branch_id' => $this->malang->id, 'vehicle_type_id' => $unit->vehicle_type_id, 'name' => 'Rekomendasi Malang']);
        Vehicle::factory()->create(['branch_id' => $this->batu->id, 'vehicle_type_id' => $unit->vehicle_type_id, 'name' => 'Rekomendasi Batu']);

        $this->get(route('kendaraan.detail', $unit))
            ->assertOk()
            ->assertSee('Rekomendasi Malang')
            ->assertDontSee('Rekomendasi Batu');

        $this->assertSame('malang', session('branch'));
    }

    public function test_detail_motor_cabang_nonaktif_404(): void
    {
        $tutup = Branch::factory()->create(['is_active' => false]);
        $unit = Vehicle::factory()->create(['branch_id' => $tutup->id]);

        $this->get(route('kendaraan.detail', $unit))->assertNotFound();
    }

    public function test_tentang_menampilkan_alamat_jam_kontak_dan_sosmed_tiap_cabang(): void
    {
        $this->malang->update(['phone' => '08123456789', 'social_links' => ['instagram' => 'https://instagram.com/malang']]);

        $this->get('/tentang')
            ->assertOk()
            ->assertSee('Cabang Malang')->assertSee('Jl. Ijen 1')->assertSee('Jam Malang')
            ->assertSee('https://wa.me/628123456789', false)
            ->assertSee('https://instagram.com/malang', false)
            ->assertSee('Cabang Batu')->assertSee('Jl. Panglima 2')->assertSee('Jam Batu');
    }

    public function test_footer_menampilkan_semua_cabang_aktif_dan_cabang_baru_otomatis_muncul(): void
    {
        $this->get('/syarat-ketentuan')->assertOk()->assertSee('Jl. Ijen 1')->assertSee('Jl. Panglima 2');

        Branch::factory()->create(['name' => 'Cabang Surabaya', 'address' => 'Jl. Darmo 3']);

        $this->get('/syarat-ketentuan')->assertOk()->assertSee('Cabang Surabaya')->assertSee('Jl. Darmo 3');
    }

    public function test_tanpa_cabang_aktif_halaman_publik_tetap_terbuka(): void
    {
        Vehicle::query()->forceDelete();
        Branch::query()->update(['is_active' => false]);

        $this->get('/')->assertOk();
        $this->get('/kendaraan')->assertOk();
        $this->get('/tentang')->assertOk()->assertSee('Alamat lokasi pengambilan akan diinformasikan oleh admin');
    }

    public function test_jumlah_query_home_dan_katalog_tidak_bertambah_per_cabang_atau_motor(): void
    {
        setting('brand.name');

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get('/')->assertOk();
        $this->get('/tentang')->assertOk();
        $kecil = count(DB::getQueryLog());

        foreach (range(1, 4) as $i) {
            $branch = Branch::factory()->create(['social_links' => ['instagram' => 'https://instagram.com/x'.$i]]);
            foreach (range(1, 2) as $j) {
                Vehicle::factory()->create(['branch_id' => $branch->id, 'name' => "Motor {$i}-{$j}"]);
            }
        }

        DB::flushQueryLog();
        $this->get('/')->assertOk();
        $this->get('/tentang')->assertOk();

        $this->assertSame($kecil, count(DB::getQueryLog()));
    }
}
