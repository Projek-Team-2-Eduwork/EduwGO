<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CabangPublicTest extends TestCase
{
    use RefreshDatabase;

    private function vehicle(string $name, ?VehicleType $type = null, array $attributes = []): Vehicle
    {
        return Vehicle::factory()->create(array_merge([
            'name' => $name,
            'vehicle_type_id' => ($type ?? VehicleType::factory()->create())->id,
        ], $attributes));
    }

    private function book(Vehicle $vehicle, Carbon $start, Carbon $end, BookingStatus $status = BookingStatus::Paid): Booking
    {
        return Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => $status,
            'start_at' => $start,
            'end_at' => $end,
        ]);
    }

    private function futureStart(): Carbon
    {
        return now()->addDays(3)->startOfHour();
    }

    public function test_daftar_cabang_tampil_dan_hanya_cabang_aktif(): void
    {
        Branch::factory()->create(['name' => 'Cabang Aktif', 'slug' => 'cabang-aktif']);
        Branch::factory()->create(['name' => 'Cabang Tutup', 'slug' => 'cabang-tutup', 'is_active' => false]);

        $this->get('/cabang')
            ->assertOk()
            ->assertSee('Cabang Aktif')
            ->assertDontSee('Cabang Tutup');
    }

    public function test_daftar_menampilkan_jumlah_unit_aktif_saja(): void
    {
        $cabang = Branch::factory()->create(['name' => 'Cabang Utama', 'slug' => 'cabang-utama']);
        $this->vehicle('Motor Satu', null, ['branch_id' => $cabang->id]);
        $this->vehicle('Motor Dua', null, ['branch_id' => $cabang->id]);
        $this->vehicle('Motor Nonaktif', null, ['branch_id' => $cabang->id, 'is_active' => false]);

        $this->get('/cabang')->assertOk()->assertSee('2 unit aktif');
    }

    public function test_halaman_cabang_hanya_menampilkan_unit_cabang_itu(): void
    {
        $a = Branch::factory()->create(['name' => 'Cabang A', 'slug' => 'cabang-a']);
        $b = Branch::factory()->create(['name' => 'Cabang B', 'slug' => 'cabang-b']);
        $this->vehicle('Motor Alpha', null, ['branch_id' => $a->id]);
        $this->vehicle('Motor Beta', null, ['branch_id' => $b->id]);

        $this->get('/cabang/cabang-a')
            ->assertOk()
            ->assertSee('Motor Alpha')
            ->assertDontSee('Motor Beta');
    }

    public function test_halaman_cabang_tanpa_unit_menampilkan_empty_state(): void
    {
        Branch::factory()->create(['slug' => 'cabang-kosong']);

        $this->get('/cabang/cabang-kosong')->assertOk()->assertSee('Belum ada motor di cabang ini');
    }

    public function test_slug_salah_dan_cabang_nonaktif_404(): void
    {
        Branch::factory()->create(['slug' => 'cabang-tutup', 'is_active' => false]);

        $this->get('/cabang/tidak-ada')->assertNotFound();
        $this->get('/cabang/cabang-tutup')->assertNotFound();
    }

    public function test_filter_tipe_bekerja(): void
    {
        $cabang = Branch::factory()->create(['slug' => 'cabang-utama']);
        $matic = VehicleType::factory()->create(['name' => 'Matic']);
        $sport = VehicleType::factory()->create(['name' => 'Sport']);
        $this->vehicle('Motor Matic A', $matic, ['branch_id' => $cabang->id]);
        $this->vehicle('Motor Sport B', $sport, ['branch_id' => $cabang->id]);

        $this->get('/cabang/cabang-utama?type='.$matic->id)
            ->assertOk()
            ->assertSee('Motor Matic A')
            ->assertDontSee('Motor Sport B');
    }

    public function test_filter_waktu_menyembunyikan_unit_terbooking(): void
    {
        $cabang = Branch::factory()->create(['slug' => 'cabang-utama']);
        $bebas = $this->vehicle('Motor Bebas', null, ['branch_id' => $cabang->id]);
        $sibuk = $this->vehicle('Motor Sibuk', null, ['branch_id' => $cabang->id]);

        $this->book($sibuk, $this->futureStart(), $this->futureStart()->addDay(), BookingStatus::Paid);

        $start = $this->futureStart()->format('Y-m-d\TH:i');
        $this->get('/cabang/cabang-utama?start_at='.$start.'&days=1')
            ->assertOk()
            ->assertSee('Motor Bebas')
            ->assertDontSee('Motor Sibuk');
    }

    public function test_n1_query_tidak_bertambah_per_unit(): void
    {
        // Panaskan cache settings dulu supaya hitungan query antar GET setara.
        setting('brand.name');

        $cabang = Branch::factory()->create();
        Vehicle::factory()->count(2)->create(['branch_id' => $cabang->id]);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/cabang/'.$cabang->slug)->assertOk();
        $kecil = count(DB::getQueryLog());

        Vehicle::factory()->count(6)->create(['branch_id' => $cabang->id]);
        DB::flushQueryLog();
        $this->get('/cabang/'.$cabang->slug)->assertOk();

        $this->assertSame($kecil, count(DB::getQueryLog()));
    }

    public function test_reset_filter_kembali_ke_semua_unit(): void
    {
        $cabang = Branch::factory()->create(['slug' => 'cabang-utama']);
        $sport = VehicleType::factory()->create(['name' => 'Sport']);
        $this->vehicle('Motor Matic A', null, ['branch_id' => $cabang->id]);
        $this->vehicle('Motor Sport B', $sport, ['branch_id' => $cabang->id]);

        $this->get('/cabang/cabang-utama?type='.$sport->id)->assertDontSee('Motor Matic A');

        $this->followingRedirects()
            ->get('/cabang/cabang-utama/reset')
            ->assertOk()
            ->assertSee('Motor Matic A')
            ->assertSee('Motor Sport B');
    }

    public function test_tautan_lama_lokasi_dialihkan_ke_cabang(): void
    {
        $this->get('/lokasi')->assertRedirect('/cabang')->assertStatus(301);
        $this->get('/lokasi/cabang-a')->assertRedirect('/cabang/cabang-a')->assertStatus(301);
    }
}
