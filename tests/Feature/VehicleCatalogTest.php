<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
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
            ->assertSee('Disewa')
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
            ->assertSee('Menampilkan motor yang tersedia mulai');
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
}
