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
        $admin = User::factory()->create(['branch_id' => $this->defaultBranch()->id]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_export_menghasilkan_file_xlsx()
    {
        $admin = $this->getAdmin();
        $vehicle = Vehicle::factory()->create();

        $bookingIn = Booking::factory()->create([
            'status' => BookingStatus::Paid,
            'vehicle_id' => $vehicle->id,
            'code' => 'IN_RANGE',
        ]);
        Payment::create([
            'booking_id' => $bookingIn->id,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
            'amount' => 500000,
        ]);

        $bookingOut = Booking::factory()->create([
            'status' => BookingStatus::Paid,
            'vehicle_id' => $vehicle->id,
            'code' => 'OUT_RANGE',
        ]);
        Payment::create([
            'booking_id' => $bookingOut->id,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now()->subMonths(3),
            'amount' => 500000,
        ]);

        $dari = now()->subWeek()->toDateString();
        $sampai = now()->addWeek()->toDateString();

        $response = $this->actingAs($admin)->get(route('admin.dashboard.export', [
            'dari' => $dari,
            'sampai' => $sampai,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_export_berdasarkan_metode_pembayaran()
    {
        $admin = $this->getAdmin();
        $vehicle = Vehicle::factory()->create();

        $bookingCash = Booking::factory()->create([
            'status' => BookingStatus::Paid,
            'vehicle_id' => $vehicle->id,
        ]);
        Payment::create([
            'booking_id' => $bookingCash->id,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
            'amount' => 500000,
        ]);

        $bookingXendit = Booking::factory()->create([
            'status' => BookingStatus::Paid,
            'vehicle_id' => $vehicle->id,
        ]);
        Payment::create([
            'booking_id' => $bookingXendit->id,
            'method' => 'xendit_invoice',
            'status' => 'paid',
            'paid_at' => now(),
            'amount' => 350000,
        ]);

        $dari = now()->subWeek()->toDateString();
        $sampai = now()->addWeek()->toDateString();

        $response = $this->actingAs($admin)->get(route('admin.dashboard.export', [
            'dari' => $dari,
            'sampai' => $sampai,
            'metode' => 'xendit_invoice',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }
}
