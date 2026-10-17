<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DashboardService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** Superadmin: widget menghitung semua cabang, sama dengan query independen di bawah. */
    private function getAdmin()
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        return $admin;
    }

    public function test_angka_widget_service_cocok_dengan_query_independen()
    {
        $service = new DashboardService;

        $stats = $service->stats();

        $expectedActiveVehicles = Vehicle::where('is_active', true)->count();
        $expectedRented = Booking::where('status', 'rented')->count();
        $expectedAvailable = $expectedActiveVehicles - $expectedRented;
        $expectedActiveBookings = Booking::whereIn('status', ['pending', 'paid', 'rented'])->count();

        $this->assertEquals($expectedActiveVehicles, $stats['total_vehicles']);
        $this->assertEquals($expectedAvailable, $stats['available_vehicles']);
        $this->assertEquals($expectedActiveBookings, $stats['active_bookings']);
        $this->assertEquals($expectedRented, $stats['rented_bookings']);

        $top = $service->topVehicles();
        $rawTop = DB::table('bookings')
            ->whereIn('status', ['paid', 'rented', 'returned'])
            ->selectRaw('vehicle_id, count(id) as count')
            ->groupBy('vehicle_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $this->assertCount($rawTop->count(), $top);
        if ($rawTop->count() > 0) {
            $this->assertEquals($rawTop->first()->count, $top->first()->booking_count);
        }

        $from = now()->startOfMonth();
        $to = now()->endOfMonth();
        $revenue = $service->revenue($from, $to);

        $expectedRevenue = Payment::join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->where('payments.status', 'paid')
            ->where('bookings.status', '!=', 'cancelled')
            ->whereBetween('payments.paid_at', [$from, $to])
            ->sum('payments.amount');

        $this->assertEquals((float) $expectedRevenue, $revenue);

        $recent = $service->recentBookings();
        $expectedRecent = Booking::orderByDesc('created_at')->limit(8)->get();
        $this->assertCount($expectedRecent->count(), $recent);
        if ($expectedRecent->count() > 0) {
            $this->assertEquals($expectedRecent->first()->id, $recent->first()->id);
        }
    }

    public function test_filter_pendapatan_berfungsi_dan_cocok()
    {
        $admin = $this->getAdmin();
        $vehicle = Vehicle::first();

        $dari = now()->subMonths(2)->startOfMonth()->toDateString();
        $sampai = now()->subMonths(1)->endOfMonth()->toDateString();

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'dari' => $dari,
            'sampai' => $sampai,
            'tipe' => $vehicle->vehicle_type_id,
        ]));

        $response->assertOk();

        $expectedRevenue = Payment::join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->join('vehicles', 'bookings.vehicle_id', '=', 'vehicles.id')
            ->where('payments.status', 'paid')
            ->where('bookings.status', '!=', 'cancelled')
            ->where('vehicles.vehicle_type_id', $vehicle->vehicle_type_id)
            ->whereBetween('payments.paid_at', [Carbon::parse($dari)->startOfDay(), Carbon::parse($sampai)->endOfDay()])
            ->sum('payments.amount');

        $response->assertSee(number_format($expectedRevenue, 0, ',', '.'));
    }

    public function test_filter_pendapatan_berdasarkan_metode_pembayaran()
    {
        $admin = $this->getAdmin();

        $booking = Booking::factory()->create(['status' => 'paid']);
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'method' => 'cash',
            'status' => 'paid',
            'amount' => 150000,
            'paid_at' => now(),
        ]);

        Payment::factory()->create([
            'booking_id' => $booking->id,
            'method' => 'xendit_invoice',
            'status' => 'paid',
            'amount' => 300000,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'metode' => 'cash',
        ]));

        $response->assertOk();

        $service = new DashboardService;
        $cashRevenue = $service->revenue(now()->startOfDay(), now()->endOfDay(), null, 'cash');

        $this->assertEquals(150000, $cashRevenue);
    }

    public function test_dashboard_tampil_benar_dan_render_semua_widget()
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();

        $response->assertSee('Total Unit Aktif');
        $response->assertSee('Tersedia');
        $response->assertSee('5 Top Orderan Rental');
        $response->assertSee('Pendapatan');
        $response->assertSee('Total Pendapatan');
        $response->assertSee('Pesanan Terakhir');
        $response->assertSee('Lihat semua');
    }

    // 8 query dasar + 1 query nama cabang admin di header layout (konstan, bukan N+1)
    public function test_batas_maksimal_query_halaman_adalah_9()
    {
        $admin = $this->getAdmin();

        setting('brand.name');

        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();

        $this->assertLessThanOrEqual(9, count(DB::getQueryLog()));
    }
}
