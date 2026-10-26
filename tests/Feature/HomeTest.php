<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_tampil_dengan_default_bila_settings_kosong(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Sewa motor harian', false)
            ->assertDontSee('Rp75.000')
            ->assertSee('Cara sewa')
            ->assertSee('Syarat sewa', false)
            ->assertSee('SIM C aktif', false)
            ->assertSee(url('/kendaraan'));
    }

    public function test_katalog_ringkas_maksimal_8_motor_aktif_dengan_badge_status(): void
    {
        $type = VehicleType::factory()->create();
        Vehicle::factory()->count(9)->create(['vehicle_type_id' => $type->id, 'is_active' => true]);
        $nonaktif = Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'is_active' => false, 'name' => 'Motor Nonaktif']);

        $response = $this->get('/')->assertOk()->assertDontSee($nonaktif->name);

        $this->assertCount(8, $response->viewData('vehicles'));
        $this->assertSame(9, $response->viewData('activeVehicleCount'));
        $response->assertSee('9 motor aktif');
        $response->assertSee('Pilih jadwal untuk cek');
        $response->assertSee('Lihat semua');
        $response->assertSee('Pesan');
        $response->assertSee(route('kendaraan.detail', $response->viewData('vehicles')->first()).'#pesan', false);
    }

    public function test_motor_dengan_booking_aktif_sekarang_berbadge_disewa(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Rented,
            'start_at' => now()->subHour(),
            'end_at' => now()->addDay(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Sedang disewa')
            ->assertViewHas('availableIds', fn ($ids) => ! in_array($vehicle->id, $ids, true));
    }

    public function test_tanpa_motor_menampilkan_empty_state(): void
    {
        $this->get('/')->assertOk()->assertSee('Belum ada motor tersedia');
    }

    public function test_jumlah_query_katalog_tidak_bertambah_per_motor(): void
    {
        // Panaskan cache settings dulu: GET pertama saat cache dingin menambah
        // 2 query (hasTable + pluck) dan membuat hitungan kedua GET tidak setara.
        setting('brand.name');

        Vehicle::factory()->count(2)->create();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/')->assertOk();
        $kecil = count(DB::getQueryLog());

        Vehicle::factory()->count(6)->create();
        DB::flushQueryLog();
        $this->get('/')->assertOk();

        $this->assertSame($kecil, count(DB::getQueryLog()));
    }

    public function test_section_profil_menampilkan_data_tiap_cabang_aktif(): void
    {
        Setting::updateOrCreate(['key' => 'brand.description'], ['value' => 'Kami menyediakan motor terawat untuk perjalanan Anda.']);
        Branch::factory()->create([
            'name' => 'Cabang Uji A',
            'address' => 'Jl. Test Alamat No. 7, Malang',
            'phone' => '08123999888',
            'operating_hours' => 'Senin - Sabtu, 08.00 - 17.00',
            'social_links' => ['instagram' => 'https://instagram.com/eduwgo.a'],
        ]);
        Branch::factory()->create(['name' => 'Cabang Uji B', 'address' => 'Jl. Kedua No. 2, Batu', 'operating_hours' => 'Setiap hari, 09.00 - 18.00']);
        Branch::factory()->create(['name' => 'Cabang Tutup', 'address' => 'Jl. Tutup', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Lokasi cabang')
            ->assertSee('Cabang Uji A')
            ->assertSee('Jl. Test Alamat No. 7, Malang')
            ->assertSee('Senin - Sabtu, 08.00 - 17.00')
            ->assertSee('https://wa.me/628123999888', false)
            ->assertSee('Cabang Uji B')
            ->assertSee('Jl. Kedua No. 2, Batu')
            ->assertDontSee('Cabang Tutup')
            ->assertSee('Buka peta');
    }

    public function test_section_profil_perusahaan_tampil_default_tanpa_cabang(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Lokasi cabang')
            ->assertSee('Alamat lokasi pengambilan akan diinformasikan oleh admin');
    }

    public function test_deskripsi_profil_menghapus_klaim_lama_dari_pengaturan(): void
    {
        Setting::updateOrCreate(['key' => 'brand.description'], ['value' => 'EduwGo menyediakan rental motor cepat, aman, dan terjangkau. Pilih jadwal Anda.']);

        $this->get('/')->assertOk()->assertViewHas('profil', fn ($profil) => $profil['deskripsi'] === 'EduwGo menyediakan rental motor. Pilih jadwal Anda.');
    }
}
