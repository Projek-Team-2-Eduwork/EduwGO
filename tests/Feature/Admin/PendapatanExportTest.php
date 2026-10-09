<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PendapatanExportTest extends TestCase
{
    use RefreshDatabase;

    private function getAdmin()
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_export_menghasilkan_file_xlsx()
    {
        $admin = $this->getAdmin();
        $vehicle = Vehicle::factory()->create();

        // Data dalam rentang
        $bookingIn = Booking::factory()->create(['status' => BookingStatus::Paid, 'vehicle_id' => $vehicle->id, 'code' => 'IN_RANGE']);
        Payment::create(['booking_id' => $bookingIn->id, 'method' => 'cash', 'status' => 'paid', 'paid_at' => now(), 'amount' => 500000]);

        // Data di luar rentang
        $bookingOut = Booking::factory()->create(['status' => BookingStatus::Paid, 'vehicle_id' => $vehicle->id, 'code' => 'OUT_RANGE']);
        Payment::create(['booking_id' => $bookingOut->id, 'method' => 'cash', 'status' => 'paid', 'paid_at' => now()->subMonths(3), 'amount' => 500000]);

        $dari = now()->subWeek()->toDateString();
        $sampai = now()->addWeek()->toDateString();

        $response = $this->actingAs($admin)->get(route('admin.dashboard.export', [
            'dari' => $dari,
            'sampai' => $sampai,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }
}
