<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\AvailabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AvailabilityService::class);
    }

    private function bookingAktif(Vehicle $vehicle, string $mulai, string $selesai, string $state = 'paid'): Booking
    {
        return Booking::factory()->{$state}()->create([
            'vehicle_id' => $vehicle->id,
            'start_at' => $mulai,
            'end_at' => $selesai,
        ]);
    }

    // ===== isAvailable =====

    public function test_kendaraan_tanpa_booking_tersedia(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        $this->assertTrue($this->service->isAvailable($vehicle, Carbon::parse('2026-10-10 10:00:00'), 1));
    }

    public function test_tidak_overlap_menjadi_tersedia(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        $this->bookingAktif($vehicle, '2026-10-10 10:00:00', '2026-10-10 12:00:00');

        // Mulai tepat setelah selesai + buffer 60 menit → lolos
        $this->assertTrue($this->service->isAvailable($vehicle, Carbon::parse('2026-10-10 13:00:00'), 1));
    }

    public function test_overlap_sebagian_memblokir(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        $this->bookingAktif($vehicle, '2026-10-10 10:00:00', '2026-10-10 12:00:00');

        // Sisi kiri: start jatuh di dalam booking lama
        $this->assertFalse($this->service->isAvailable($vehicle, Carbon::parse('2026-10-10 11:00:00'), 1));

        // Sisi kanan: booking lama jatuh di awal rentang baru
        $this->assertFalse($this->service->isAvailable($vehicle, Carbon::parse('2026-10-10 09:00:00'), 2));
    }

    public function test_overlap_penuh_memblokir(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        $this->bookingAktif($vehicle, '2026-10-10 14:00:00', '2026-10-10 15:00:00');

        // Rentang baru mencakup penuh booking lama
        $this->assertFalse($this->service->isAvailable($vehicle, Carbon::parse('2026-10-10 10:00:00'), 1));
    }

    public function test_buffer_59_menit_bentrok_61_menit_lolos(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        $this->bookingAktif($vehicle, '2026-10-10 10:00:00', '2026-10-10 17:00:00');

        // Jeda 59 menit setelah selesai → masih di dalam buffer 60 menit → bentrok
        $this->assertFalse($this->service->isAvailable($vehicle, Carbon::parse('2026-10-10 17:59:00'), 1));

        // Jeda 61 menit → lewat buffer → lolos
        $this->assertTrue($this->service->isAvailable($vehicle, Carbon::parse('2026-10-10 18:01:00'), 1));
    }

    public function test_boundary_buffer_persis_60_menit(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);
        $this->bookingAktif($vehicle, '2026-10-10 10:00:00', '2026-10-10 17:00:00');

        // Mulai 17:30 → masih dalam buffer → bentrok
        $this->assertFalse($this->service->isAvailable($vehicle, Carbon::parse('2026-10-10 17:30:00'), 1));

        // Mulai tepat 18:00 (buffer berakhir) → lolos (konflik strict: end_at + buffer > start)
        $this->assertTrue($this->service->isAvailable($vehicle, Carbon::parse('2026-10-10 18:00:00'), 1));
    }

    public function test_booking_cancelled_dan_expired_diabaikan(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        $this->bookingAktif($vehicle, '2026-10-10 12:00:00', '2026-10-10 15:00:00', 'cancelled');
        $this->bookingAktif($vehicle, '2026-10-10 12:00:00', '2026-10-10 15:00:00', 'expired');

        $this->assertTrue($this->service->isAvailable($vehicle, Carbon::parse('2026-10-10 13:00:00'), 1));
    }

    public function test_status_pending_paid_rented_memblokir(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        foreach (['pending', 'paid', 'rented'] as $state) {
            $booking = $this->bookingAktif($vehicle, '2026-10-10 12:00:00', '2026-10-10 15:00:00', $state);

            $this->assertFalse(
                $this->service->isAvailable($vehicle, Carbon::parse('2026-10-10 13:00:00'), 1),
                "Status {$state} gagal memblokir"
            );

            $booking->delete(); // bersihkan untuk iterasi berikutnya
        }
    }

    public function test_days_dihitung_dari_start(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        // Booking jatuh pada hari kedua rentang
        $this->bookingAktif($vehicle, '2026-10-12 00:00:00', '2026-10-12 23:00:00');

        $start = Carbon::parse('2026-10-10 10:00:00');

        // 1 hari → window s/d 11 Okt 10:00 → booking 12 Okt di luar → tersedia
        $this->assertTrue($this->service->isAvailable($vehicle, $start, 1));

        // 2 hari → window s/d 12 Okt 10:00 → booking masuk → diblokir
        $this->assertFalse($this->service->isAvailable($vehicle, $start, 2));
    }

    public function test_vehicle_tidak_aktif_tidak_tersedia(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => false]);

        $this->assertFalse($this->service->isAvailable($vehicle, Carbon::parse('2026-10-10 10:00:00'), 1));
    }

    // ===== availableVehicles =====

    public function test_available_vehicles_builder_filter_tipe_search_dan_status(): void
    {
        $typeA = VehicleType::factory()->create();
        $typeB = VehicleType::factory()->create();

        $vario = Vehicle::factory()->create(['vehicle_type_id' => $typeA->id, 'is_active' => true, 'name' => 'Vario Matic']);
        $pcx = Vehicle::factory()->create(['vehicle_type_id' => $typeB->id, 'is_active' => true, 'name' => 'PCX Matic']);
        Vehicle::factory()->create(['vehicle_type_id' => $typeA->id, 'is_active' => false, 'name' => 'Beat Matic']);

        // PCX dibooking di rentang yang sama → bentrok
        $this->bookingAktif($pcx, '2026-10-10 12:00:00', '2026-10-10 15:00:00');

        $start = Carbon::parse('2026-10-10 10:00:00');

        // Hasil berupa Builder (bukan Collection) supaya bisa ->paginate()
        $query = $this->service->availableVehicles($start, 1);
        $this->assertInstanceOf(Builder::class, $query);

        // Tanpa filter: hanya Vario (PCX bentrok, Beat nonaktif)
        $this->assertSame([$vario->id], $query->pluck('id')->all());

        // Filter tipe: tipe B hanya berisi PCX yang bentrok → kosong
        $this->assertSame([], $this->service->availableVehicles($start, 1, $typeB->id)->pluck('id')->all());

        // Filter search nama: "Matic" → hanya Vario
        $this->assertSame([$vario->id], $this->service->availableVehicles($start, 1, null, 'Matic')->pluck('id')->all());
    }

    public function test_available_vehicles_paginate_tanpa_n_plus_1(): void
    {
        $start = Carbon::parse('2026-10-10 10:00:00');

        // 10 unit, tiap unit genap dibooking di rentang yang sama
        foreach (range(1, 10) as $i) {
            $vehicle = Vehicle::factory()->create(['is_active' => true]);

            if ($i % 2 === 0) {
                $this->bookingAktif($vehicle, '2026-10-10 12:00:00', '2026-10-10 15:00:00');
            }
        }

        DB::enableQueryLog();
        DB::flushQueryLog();

        $halaman = $this->service->availableVehicles($start, 1)->paginate(10);

        $jumlahQuery = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 5 tersedia dari 10 unit
        $this->assertSame(5, $halaman->total());
        $this->assertCount(5, $halaman->items());

        // count + select halaman + eager load tipe = 3 query; N+1 akan > 10 query
        $this->assertLessThanOrEqual(6, $jumlahQuery);
    }

    // ===== availableDurations =====

    public function test_available_durations_memberi_1_sampai_max_hari(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        // Booking memblokir hari ke-3
        $this->bookingAktif($vehicle, '2026-10-12 12:00:00', '2026-10-12 15:00:00');

        $hasil = $this->service->availableDurations($vehicle, Carbon::parse('2026-10-10 10:00:00'), 3);

        $this->assertSame([1, 2, 3], array_keys($hasil));
        $this->assertTrue($hasil[1]['available']);
        $this->assertTrue($hasil[2]['available']);
        $this->assertFalse($hasil[3]['available']);
        $this->assertSame('2026-10-11T10:00:00', Carbon::parse($hasil[1]['end_at'])->format('Y-m-d\TH:i:s'));
    }

    // ===== bookedRanges =====

    public function test_booked_ranges_collection_termasuk_buffer(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->bookingAktif($vehicle, '2026-10-10 10:00:00', '2026-10-10 15:00:00');

        $hasil = $this->service->bookedRanges(
            $vehicle,
            Carbon::parse('2026-10-10 08:00:00'),
            Carbon::parse('2026-10-10 20:00:00')
        );

        $this->assertInstanceOf(Collection::class, $hasil);
        $this->assertCount(1, $hasil);

        // Buffer 60 menit ditarik ke dua sisi
        $this->assertTrue(Carbon::parse('2026-10-10 09:00:00')->equalTo($hasil[0]['mulai']));
        $this->assertTrue(Carbon::parse('2026-10-10 16:00:00')->equalTo($hasil[0]['selesai']));
    }

    // ===== Scope Booking =====

    public function test_scope_active_dan_overlapping_dipakai_ulang(): void
    {
        $this->bookingAktif(Vehicle::factory()->create(), '2026-10-10 10:00:00', '2026-10-10 12:00:00', 'paid');
        $this->bookingAktif(Vehicle::factory()->create(), '2026-10-10 10:00:00', '2026-10-10 12:00:00', 'cancelled');

        // active(): hanya pending/paid/rented yang masuk
        $this->assertSame(1, Booking::active()->count());

        // overlapping + buffer 60 menit: rentang 12:30–13:30 menyentuh ekor buffer booking (selesai 12:00)
        $this->assertSame(1, Booking::active()->overlapping(
            Carbon::parse('2026-10-10 12:30:00'),
            Carbon::parse('2026-10-10 13:30:00'),
            60
        )->count());

        // overlapping tanpa menyentuh buffer → tidak ketemu
        $this->assertSame(0, Booking::active()->overlapping(
            Carbon::parse('2026-10-10 14:00:00'),
            Carbon::parse('2026-10-10 15:00:00'),
            60
        )->count());
    }
}
