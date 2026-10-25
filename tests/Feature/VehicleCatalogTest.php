<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VehicleCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function vehicle(string $name, ?VehicleType $type = null, array $attributes = []): Vehicle
    {
        return Vehicle::factory()->create(array_merge([
            'name' => $name,
            'vehicle_type_id' => ($type ?? VehicleType::factory()->create())->id,
        ], $attributes));
    }

    private function branch(string $name, string $slug): Branch
    {
        return Branch::factory()->create(['name' => $name, 'slug' => $slug]);
    }

    private function book(Vehicle $vehicle, string $start, string $end, BookingStatus $status = BookingStatus::Paid): Booking
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

    public function test_katalog_menampilkan_semua_motor_aktif_tanpa_filter(): void
    {
        $matic = VehicleType::factory()->create(['name' => 'Matic']);
        $this->vehicle('Honda Vario 160', $matic);
        $this->vehicle('Yamaha NMAX 155', $matic);
        $this->vehicle('Honda Beat 110', $matic, ['is_active' => false]);

        $this->get('/kendaraan')
            ->assertOk()
            ->assertSee('Honda Vario 160')
            ->assertSee('Yamaha NMAX 155')
            ->assertDontSee('Honda Beat 110');
    }

    public function test_filter_tipe_hanya_menampilkan_tipe_terpilih(): void
    {
        $matic = VehicleType::factory()->create(['name' => 'Matic']);
        $cub = VehicleType::factory()->create(['name' => 'Cub']);
        $this->vehicle('Honda Vario 160', $matic);
        $this->vehicle('Honda Beat 110', $cub);

        $this->get('/kendaraan?type='.$cub->id)
            ->assertOk()
            ->assertSee('Honda Beat 110')
            ->assertDontSee('Honda Vario 160');
    }

    public function test_cari_nama_menyaring_hasil(): void
    {
        $this->vehicle('Honda Vario 160');
        $this->vehicle('Yamaha NMAX 155');

        $this->get('/kendaraan?q=nmax')
            ->assertOk()
            ->assertSee('Yamaha NMAX 155')
            ->assertDontSee('Honda Vario 160');
    }

    public function test_filter_waktu_menyembunyikan_motor_yang_bentrok(): void
    {
        $start = $this->futureStart();
        $bentrok = $this->vehicle('Honda Vario 160');
        $this->vehicle('Yamaha NMAX 155');

        $this->book($bentrok, $start->copy()->addHours(2)->toDateTimeString(), $start->copy()->addDay()->toDateTimeString());

        $this->get('/kendaraan?'.http_build_query(['start_at' => $start->format('Y-m-d\TH:i'), 'days' => 2]))
            ->assertOk()
            ->assertSee('Yamaha NMAX 155')
            ->assertDontSee('Honda Vario 160');
    }

    public function test_booking_yang_sudah_batal_tidak_menyembunyikan_motor(): void
    {
        $start = $this->futureStart();
        $motor = $this->vehicle('Honda Vario 160');

        $this->book($motor, $start->copy()->addHour()->toDateTimeString(), $start->copy()->addDay()->toDateTimeString(), BookingStatus::Cancelled);

        $this->get('/kendaraan?'.http_build_query(['start_at' => $start->format('Y-m-d\TH:i'), 'days' => 1]))
            ->assertSee('Honda Vario 160');
    }

    public function test_semua_motor_bentrok_menampilkan_empty_state(): void
    {
        $start = $this->futureStart();
        $motor = $this->vehicle('Honda Vario 160');

        $this->book($motor, $start->toDateTimeString(), $start->copy()->addDays(2)->toDateTimeString());

        $this->get('/kendaraan?'.http_build_query(['start_at' => $start->format('Y-m-d\TH:i'), 'days' => 1]))
            ->assertOk()
            ->assertSee('Tidak ada motor tersedia di waktu tersebut')
            ->assertDontSee('Honda Vario 160');
    }

    public function test_toggle_hanya_tersedia_menyembunyikan_motor_yang_sedang_disewa(): void
    {
        $disewa = $this->vehicle('Honda Vario 160');
        $this->vehicle('Yamaha NMAX 155');
        $this->book($disewa, now()->subHour()->toDateTimeString(), now()->addDay()->toDateTimeString(), BookingStatus::Rented);

        // Tanpa toggle: kedua motor tampil, yang disewa berbadge "Disewa".
        $this->get('/kendaraan')
            ->assertSee('Honda Vario 160')
            ->assertSee('Sedang disewa')
            ->assertSee('Yamaha NMAX 155');

        $this->get('/kendaraan?available=1')
            ->assertSee('Yamaha NMAX 155')
            ->assertDontSee('Honda Vario 160');
    }

    public function test_filter_disimpan_di_session_dan_bertahan_saat_kembali_dari_detail(): void
    {
        $start = $this->futureStart();
        $motor = $this->vehicle('Honda Vario 160');
        $query = ['start_at' => $start->format('Y-m-d\TH:i'), 'days' => 2, 'type' => $motor->vehicle_type_id];

        $this->get('/kendaraan?'.http_build_query($query))
            ->assertSessionHas('booking.filter', $query)
            ->assertSessionHas('booking.start_at', $query['start_at'])
            // Tombol Booking membawa filter ke detail
            ->assertSee(e(route('kendaraan.detail', ['vehicle' => $motor, 'start' => $query['start_at'], 'days' => 2])), false);

        // Pindah ke detail lalu kembali ke /kendaraan tanpa query: filter dipulihkan dari session.
        $this->get(route('kendaraan.detail', $motor))->assertOk();
        $this->get('/kendaraan')
            ->assertOk()
            ->assertSee('value="'.$query['start_at'].'"', false)
            ->assertSee('tersedia mulai');
    }

    public function test_reset_menghapus_filter_tersimpan(): void
    {
        $start = $this->futureStart();
        $this->vehicle('Honda Vario 160');

        $this->get('/kendaraan?'.http_build_query(['start_at' => $start->format('Y-m-d\TH:i'), 'days' => 1]));

        $this->get('/kendaraan/reset')
            ->assertRedirect(route('kendaraan.index'))
            ->assertSessionMissing('booking.filter');
    }

    public function test_waktu_mulai_di_masa_lalu_ditolak(): void
    {
        $this->from('/kendaraan')
            ->get('/kendaraan?'.http_build_query(['start_at' => now()->subDay()->format('Y-m-d\TH:i'), 'days' => 1]))
            ->assertRedirect('/kendaraan')
            ->assertSessionHasErrors('start_at');
    }

    public function test_durasi_melebihi_batas_dan_tanpa_waktu_mulai_ditolak(): void
    {
        $this->from('/kendaraan')
            ->get('/kendaraan?'.http_build_query(['start_at' => $this->futureStart()->format('Y-m-d\TH:i'), 'days' => 99]))
            ->assertSessionHasErrors('days');

        $this->from('/kendaraan')
            ->get('/kendaraan?days=2')
            ->assertSessionHasErrors('start_at');
    }

    public function test_filter_session_kedaluwarsa_tidak_menyebabkan_error(): void
    {
        $this->vehicle('Honda Vario 160');

        $this->withSession(['booking.filter' => ['start_at' => now()->subDays(2)->format('Y-m-d\TH:i'), 'days' => 1, 'type' => null]])
            ->get('/kendaraan')
            ->assertOk()
            ->assertSee('Honda Vario 160');
    }

    public function test_pagination_12_per_halaman_dan_tanpa_n_plus_1(): void
    {
        $matic = VehicleType::factory()->create(['name' => 'Matic']);
        foreach (range(0, 12) as $i) {
            Vehicle::create([
                'branch_id' => $this->defaultBranch()->id,
                'vehicle_type_id' => $matic->id,
                'name' => 'Motor '.str_pad($i, 2, '0', STR_PAD_LEFT),
                'slug' => 'motor-'.$i,
                'brand' => 'Honda',
                'plate_number' => 'B '.(1000 + $i).' EDU',
                'price_per_day' => 100000,
                'is_active' => true,
            ]);
        }

        $this->get('/kendaraan')->assertOk()->assertSee('Motor 11')->assertDontSee('Motor 12');
        $this->get('/kendaraan?page=2')->assertOk()->assertSee('Motor 12');

        \DB::enableQueryLog();
        $this->get('/kendaraan')->assertOk();
        $selects = collect(\DB::getQueryLog())->pluck('query')->filter(fn ($q) => str_contains($q, 'from `vehicle_types`') || str_contains($q, 'from "vehicle_types"'));
        // 1 untuk opsi filter tipe + 1 eager load; tidak bertambah per motor.
        $this->assertLessThanOrEqual(2, $selects->count());
    }

    public function test_urut_harga_termurah_dan_termahal()
    {
        $this->vehicle('Motor B', null, ['price_per_day' => 150000]);
        $this->vehicle('Motor A', null, ['price_per_day' => 90000]);
        $this->vehicle('Motor C', null, ['price_per_day' => 200000]);

        $responseTermurah = $this->get('/kendaraan?harga=termurah');
        $responseTermurah->assertOk();
        $responseTermurah->assertSeeInOrder(['Motor A', 'Motor B', 'Motor C']);

        $responseTermahal = $this->get('/kendaraan?harga=termahal');
        $responseTermahal->assertOk();
        $responseTermahal->assertSeeInOrder(['Motor C', 'Motor B', 'Motor A']);
    }

    public function test_rentang_harga()
    {
        $this->vehicle('Motor Murah', null, ['price_per_day' => 90000]);
        $this->vehicle('Motor Sedang', null, ['price_per_day' => 150000]);
        $this->vehicle('Motor Mahal', null, ['price_per_day' => 200000]);

        $response = $this->get('/kendaraan?harga_min=100000&harga_max=175000');

        $response->assertOk();
        $response->assertSee('Motor Sedang');
        $response->assertDontSee('Motor Murah');
        $response->assertDontSee('Motor Mahal');
    }

    public function test_value_harga_invalid_diabaikan()
    {
        $this->vehicle('A Motor', null, ['price_per_day' => 200000]);
        $this->vehicle('B Motor', null, ['price_per_day' => 90000]);

        // Input kotor akan diabaikan secara elegan (kembali ke default: abjad)
        $response = $this->get('/kendaraan?harga=tengah&harga_min=abc&harga_max=-5');

        $response->assertOk();
        $response->assertSeeInOrder(['A Motor', 'B Motor']);
    }

    public function test_filter_harga_bertahan_di_session()
    {
        $this->vehicle('Murah', null, ['price_per_day' => 45000]);
        $this->vehicle('Mahal', null, ['price_per_day' => 100000]);

        // Request pertama menyimpan state di session
        $this->get('/kendaraan?harga=termahal&harga_min=50000')->assertOk();

        // Request kedua tanpa string kueri
        $response = $this->get('/kendaraan');

        $response->assertOk();
        $response->assertSee('Mahal');
        $response->assertDontSee('Murah'); // Filter minimal 50k dari session masih bekerja
    }

    public function test_urut_populer_berdasarkan_jumlah_booking()
    {
        $motorA = $this->vehicle('Motor A');
        $motorB = $this->vehicle('Motor B');
        $motorC = $this->vehicle('Motor C');

        // Motor A dipesan 3x
        $this->book($motorA, now()->addDays(1), now()->addDays(2), BookingStatus::Paid);
        $this->book($motorA, now()->addDays(4), now()->addDays(5), BookingStatus::Rented);
        $this->book($motorA, now()->addDays(7), now()->addDays(8), BookingStatus::Returned);

        // Motor B dipesan 1x
        $this->book($motorB, now()->addDays(1), now()->addDays(2), BookingStatus::Paid);

        // Motor C dipesan 1x tapi pending (tidak dihitung)
        $this->book($motorC, now()->addDays(1), now()->addDays(2), BookingStatus::Pending);

        $response = $this->get('/kendaraan?urut=populer');

        $response->assertOk();
        $response->assertSeeInOrder(['Motor A', 'Motor B', 'Motor C']);
        // Jumlah booking hanya dipakai untuk urutan, tidak ditampilkan di kartu
        $response->assertDontSee('dipesan');
    }

    public function test_kartu_katalog_tidak_menampilkan_jumlah_dipesan()
    {
        $motor = $this->vehicle('Laris Manis');
        $this->book($motor, now()->addDays(1), now()->addDays(2), BookingStatus::Paid);

        $response = $this->get('/kendaraan');
        $response->assertOk();
        $response->assertDontSee('dipesan');
    }

    public function test_agregat_booking_tidak_n_plus_1()
    {
        // Satu tipe dipakai bersama; unit dibuat manual (bukan factory) karena
        // VehicleFactory memakai pool nama unik terbatas (10) yang tidak muat untuk 13 unit.
        $type = VehicleType::factory()->create();

        // Buat 13 unit (1 halaman = 12 + 1 page berikutnya)
        for ($i = 1; $i <= 13; $i++) {
            $unit = Vehicle::create([
                'branch_id' => $this->defaultBranch()->id,
                'vehicle_type_id' => $type->id,
                'name' => "Unit Test {$i}",
                'slug' => 'unit-test-'.$i,
                'brand' => 'Honda',
                'plate_number' => 'B '.(2000 + $i).' EDU',
                'price_per_day' => 100000,
                'is_active' => true,
            ]);
            // Masing-masing dipesan 2x
            $this->book($unit, now()->addDays($i), now()->addDays($i)->addHours(10), BookingStatus::Paid);
            $this->book($unit, now()->addDays(15 + $i), now()->addDays(15 + $i)->addHours(10), BookingStatus::Paid);
        }

        \Illuminate\Support\Facades\DB::enableQueryLog();

        $response = $this->get('/kendaraan?urut=populer');
        $response->assertOk();

        $separate = collect(\Illuminate\Support\Facades\DB::getQueryLog())->pluck('query')
            ->filter(fn ($q) => str_contains(strtolower($q), 'from `bookings`') && ! str_contains(strtolower($q), 'from `vehicles`'));

        // Memastikan tidak ada N+1 query (0 hit karena menggunakan withCount)
        $this->assertSame(0, $separate->count());
    }

    public function test_katalog_default_hanya_unit_cabang_terpilih_bukan_semua_cabang(): void
    {
        $a = $this->branch('Cabang A', 'cabang-a');
        $b = $this->branch('Cabang B', 'cabang-b');
        $this->vehicle('Motor Alpha', null, ['branch_id' => $a->id]);
        $this->vehicle('Motor Beta', null, ['branch_id' => $b->id]);

        $this->get('/kendaraan')
            ->assertOk()
            ->assertSee('Motor Alpha')
            ->assertDontSee('Motor Beta');
    }

    public function test_pilih_cabang_lewat_query_memfilter_katalog_dan_bertahan_di_session(): void
    {
        $a = $this->branch('Cabang A', 'cabang-a');
        $b = $this->branch('Cabang B', 'cabang-b');
        $this->vehicle('Motor Alpha', null, ['branch_id' => $a->id]);
        $this->vehicle('Motor Beta', null, ['branch_id' => $b->id]);

        $this->get('/kendaraan?cabang=cabang-b')->assertRedirect('/kendaraan');
        $this->assertSame('cabang-b', session('branch'));

        $this->get('/kendaraan')
            ->assertOk()
            ->assertSee('Motor Beta')
            ->assertDontSee('Motor Alpha');
    }

    public function test_cabang_tidak_dikenal_atau_nonaktif_diabaikan(): void
    {
        $a = $this->branch('Cabang A', 'cabang-a');
        Branch::factory()->create(['name' => 'Cabang Tutup', 'slug' => 'cabang-tutup', 'is_active' => false]);
        $this->vehicle('Motor Alpha', null, ['branch_id' => $a->id]);

        $this->get('/kendaraan?cabang=tidak-ada')->assertRedirect('/kendaraan');
        $this->get('/kendaraan?cabang=cabang-tutup')->assertRedirect('/kendaraan');
        $this->assertNull(session('branch'));

        $this->get('/kendaraan')->assertOk()->assertSee('Motor Alpha');
    }

    public function test_reset_filter_tidak_mengubah_cabang_terpilih(): void
    {
        $a = $this->branch('Cabang A', 'cabang-a');
        $b = $this->branch('Cabang B', 'cabang-b');
        $this->vehicle('Motor Alpha', null, ['branch_id' => $a->id]);
        $this->vehicle('Motor Beta', null, ['branch_id' => $b->id]);

        $this->get('/kendaraan?cabang=cabang-b');

        $this->followingRedirects()
            ->get('/kendaraan/reset')
            ->assertOk()
            ->assertSee('Motor Beta')
            ->assertDontSee('Motor Alpha');
    }

    public function test_waktu_mulai_tanpa_durasi_ditolak_berpasangan(): void
    {
        $this->from('/kendaraan')
            ->get('/kendaraan?'.http_build_query(['start_at' => $this->futureStart()->format('Y-m-d\TH:i')]))
            ->assertSessionHasErrors('days');
    }

    public function test_chip_filter_aktif_bisa_dihapus_per_kelompok(): void
    {
        $start = $this->futureStart()->format('Y-m-d\TH:i');
        $this->vehicle('Honda Vario 160', null, ['price_per_day' => 80000]);

        $response = $this->get('/kendaraan?'.http_build_query(['q' => 'vario', 'start_at' => $start, 'days' => 2, 'harga_min' => 50000, 'harga' => 'termurah']))
            ->assertOk()
            ->assertSee('“vario”')
            ->assertSee('Mulai Rp50.000/hari')
            ->assertSee('Filter (3)')
            ->assertSee(e(route('kendaraan.reset')), false);

        // Hapus jadwal membuang start_at + days bersama; kata kunci, harga, urutan tetap.
        $response->assertSee(e(route('kendaraan.index', ['q' => 'vario', 'harga' => 'termurah', 'harga_min' => 50000])), false);
        // Hapus kata kunci mempertahankan jadwal.
        $response->assertSee(e(route('kendaraan.index', ['start_at' => $start, 'days' => 2, 'harga' => 'termurah', 'harga_min' => 50000])), false);
    }

    public function test_filter_harga_saja_dihitung_aktif_dan_chip_terakhir_memakai_reset(): void
    {
        $this->vehicle('Honda Vario 160', null, ['price_per_day' => 80000]);

        $this->get('/kendaraan?harga_max=100000')
            ->assertOk()
            ->assertSee('Maks. Rp100.000/hari')
            ->assertSee('Filter (1)')
            ->assertSee('Reset filter')
            // Satu-satunya chip: hapus = reset (session ikut bersih, tidak dipulihkan lagi).
            ->assertSee('href="'.e(route('kendaraan.reset')).'" class="inline-flex min-h-11 items-center gap-2 rounded-full', false);
    }

    public function test_jumlah_hasil_pakai_total_paginator(): void
    {
        $type = VehicleType::factory()->create();
        foreach (range(1, 13) as $i) {
            Vehicle::create([
                'branch_id' => $this->defaultBranch()->id,
                'vehicle_type_id' => $type->id,
                'name' => 'Unit Hitung '.$i,
                'slug' => 'unit-hitung-'.$i,
                'brand' => 'Honda',
                'plate_number' => 'B '.(3000 + $i).' EDU',
                'price_per_day' => 100000,
                'is_active' => true,
            ]);
        }

        $this->get('/kendaraan')->assertOk()->assertSee('13 motor');
    }

    public function test_kartu_tanpa_plat_menampilkan_cabang_dan_total_sesuai_jadwal(): void
    {
        $motor = $this->vehicle('Honda Genio', null, ['plate_number' => 'B 6274 SQW', 'price_per_day' => 70000]);
        $branch = $motor->branch->name;

        $this->get('/kendaraan')
            ->assertOk()
            ->assertDontSee('B 6274 SQW')
            ->assertSee('Ambil di '.$branch)
            ->assertSee('Pilih jadwal untuk cek');

        $this->get('/kendaraan?'.http_build_query(['start_at' => $this->futureStart()->format('Y-m-d\TH:i'), 'days' => 3]))
            ->assertOk()
            ->assertSee('Tersedia untuk jadwal Anda')
            ->assertSee('Rp210.000 untuk 3 hari')
            ->assertDontSee('B 6274 SQW');
    }

    public function test_empty_state_kata_kunci_menawarkan_hapus_kata_kunci(): void
    {
        $this->vehicle('Honda Vario 160');

        $this->get('/kendaraan?q=zzzzzz')
            ->assertOk()
            ->assertSee('Tidak ada motor cocok untuk “zzzzzz”')
            ->assertSee('Hapus kata kunci')
            ->assertSee('0 motor');
    }
}
