<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Location;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LokasiPublicTest extends TestCase
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

    public function test_daftar_lokasi_tampil_dan_hanya_lokasi_aktif(): void
    {
        Location::factory()->create(['name' => 'Cabang Aktif', 'slug' => 'cabang-aktif']);
        Location::factory()->create(['name' => 'Cabang Tutup', 'slug' => 'cabang-tutup', 'is_active' => false]);

        $this->get('/lokasi')
            ->assertOk()
            ->assertSee('Cabang Aktif')
            ->assertDontSee('Cabang Tutup');
    }

    public function test_daftar_menampilkan_jumlah_unit_aktif_saja(): void
    {
        $lokasi = Location::factory()->create(['name' => 'Cabang Utama', 'slug' => 'cabang-utama']);
        $this->vehicle('Motor Satu', null, ['location_id' => $lokasi->id]);
        $this->vehicle('Motor Dua', null, ['location_id' => $lokasi->id]);
        $this->vehicle('Motor Nonaktif', null, ['location_id' => $lokasi->id, 'is_active' => false]);

        $this->get('/lokasi')->assertOk()->assertSee('2 unit aktif');
    }

    public function test_halaman_lokasi_hanya_menampilkan_unit_lokasi_itu(): void
    {
        $a = Location::factory()->create(['name' => 'Cabang A', 'slug' => 'cabang-a']);
        $b = Location::factory()->create(['name' => 'Cabang B', 'slug' => 'cabang-b']);
        $this->vehicle('Motor Alpha', null, ['location_id' => $a->id]);
        $this->vehicle('Motor Beta', null, ['location_id' => $b->id]);

        $this->get('/lokasi/cabang-a')
            ->assertOk()
            ->assertSee('Motor Alpha')
            ->assertDontSee('Motor Beta');
    }

    public function test_halaman_lokasi_tanpa_unit_menampilkan_empty_state(): void
    {
        Location::factory()->create(['slug' => 'cabang-kosong']);

        $this->get('/lokasi/cabang-kosong')->assertOk()->assertSee('Belum ada motor di lokasi ini');
    }

    public function test_slug_salah_dan_lokasi_nonaktif_404(): void
    {
        Location::factory()->create(['slug' => 'cabang-tutup', 'is_active' => false]);

        $this->get('/lokasi/tidak-ada')->assertNotFound();
        $this->get('/lokasi/cabang-tutup')->assertNotFound();
    }

    public function test_filter_tipe_bekerja(): void
    {
        $lokasi = Location::factory()->create(['slug' => 'cabang-utama']);
        $matic = VehicleType::factory()->create(['name' => 'Matic']);
        $sport = VehicleType::factory()->create(['name' => 'Sport']);
        $this->vehicle('Motor Matic A', $matic, ['location_id' => $lokasi->id]);
        $this->vehicle('Motor Sport B', $sport, ['location_id' => $lokasi->id]);

        $this->get('/lokasi/cabang-utama?type='.$matic->id)
            ->assertOk()
            ->assertSee('Motor Matic A')
            ->assertDontSee('Motor Sport B');
    }

    public function test_filter_waktu_menyembunyikan_unit_terbooking(): void
    {
        $lokasi = Location::factory()->create(['slug' => 'cabang-utama']);
        $bebas = $this->vehicle('Motor Bebas', null, ['location_id' => $lokasi->id]);
        $sibuk = $this->vehicle('Motor Sibuk', null, ['location_id' => $lokasi->id]);

        $this->book($sibuk, $this->futureStart(), $this->futureStart()->addDay(), BookingStatus::Paid);

        $start = $this->futureStart()->format('Y-m-d\TH:i');
        $this->get('/lokasi/cabang-utama?start_at='.$start.'&days=1')
            ->assertOk()
            ->assertSee('Motor Bebas')
            ->assertDontSee('Motor Sibuk');
    }

    public function test_n1_query_tidak_bertambah_per_unit(): void
    {
        // Panaskan cache settings dulu supaya hitungan query antar GET setara.
        setting('brand.name');

        $lokasi = Location::factory()->create();
        Vehicle::factory()->count(2)->create(['location_id' => $lokasi->id]);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/lokasi/'.$lokasi->slug)->assertOk();
        $kecil = count(DB::getQueryLog());

        Vehicle::factory()->count(6)->create(['location_id' => $lokasi->id]);
        DB::flushQueryLog();
        $this->get('/lokasi/'.$lokasi->slug)->assertOk();

        $this->assertSame($kecil, count(DB::getQueryLog()));
    }

    public function test_reset_filter_kembali_ke_semua_unit(): void
    {
        $lokasi = Location::factory()->create(['slug' => 'cabang-utama']);
        $sport = VehicleType::factory()->create(['name' => 'Sport']);
        $this->vehicle('Motor Matic A', null, ['location_id' => $lokasi->id]);
        $this->vehicle('Motor Sport B', $sport, ['location_id' => $lokasi->id]);

        $this->get('/lokasi/cabang-utama?type='.$sport->id)->assertDontSee('Motor Matic A');

        $this->followingRedirects()
            ->get('/lokasi/cabang-utama/reset')
            ->assertOk()
            ->assertSee('Motor Matic A')
            ->assertSee('Motor Sport B');
    }
}
