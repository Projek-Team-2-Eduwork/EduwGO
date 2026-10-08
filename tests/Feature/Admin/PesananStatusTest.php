<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PesananStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    private function getAdmin()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_paid_ke_rented_sukses_dan_history_tercatat_oleh_admin()
    {
        $admin = $this->getAdmin();
        $booking = Booking::factory()->paid()->create();

        $response = $this->actingAs($admin)->post(route('admin.pesanan.update', $booking->code), [
            'to' => 'rented',
            'note' => 'Kondisi mulus',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'rented',
        ]);

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'to_status' => 'rented',
            'changed_by' => $admin->id,
            'note' => 'Kondisi mulus',
        ]);
    }

    public function test_rented_ke_returned_sukses()
    {
        $admin = $this->getAdmin();
        $booking = Booking::factory()->rented()->create();

        $response = $this->actingAs($admin)->post(route('admin.pesanan.update', $booking->code), [
            'to' => 'returned',
            'note' => 'Aman tanpa lecet',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'returned',
        ]);
    }

    public function test_pending_ke_rented_ditolak_karena_invalid_transition()
    {
        $admin = $this->getAdmin();
        $booking = Booking::factory()->pending()->create();

        $response = $this->actingAs($admin)->post(route('admin.pesanan.update', $booking->code), [
            'to' => 'rented',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error'); // Exception message dari InvalidTransitionException

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'pending', // Status tetap ditahan pending
        ]);
    }

    public function test_batal_tanpa_alasan_ditolak_validasi()
    {
        $admin = $this->getAdmin();
        $booking = Booking::factory()->pending()->create();

        $response = $this->actingAs($admin)->post(route('admin.pesanan.update', $booking->code), [
            'to' => 'cancelled',
            'reason' => '', // Dikosongkan padahal required
        ]);

        $response->assertSessionHasErrors('reason');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'pending', // Status tetap
        ]);
    }

    public function test_user_biasa_ditolak_mengubah_status()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->paid()->create();

        $this->actingAs($user)->post(route('admin.pesanan.update', $booking->code), [
            'to' => 'rented',
        ])->assertForbidden(); // Return status 403
    }
}
