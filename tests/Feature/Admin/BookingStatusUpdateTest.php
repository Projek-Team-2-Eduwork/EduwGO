<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_paid_ke_rented_sukses_dan_history_tercatat_oleh_admin()
    {
        $admin = $this->actingAsAdmin();
        $booking = Booking::factory()->paid()->create();

        $response = $this->post(route('admin.pesanan.update', $booking->code), [
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
        $admin = $this->actingAsAdmin();
        $booking = Booking::factory()->rented()->create();

        $response = $this->post(route('admin.pesanan.update', $booking->code), [
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
        $admin = $this->actingAsAdmin();
        $booking = Booking::factory()->pending()->create();

        $response = $this->post(route('admin.pesanan.update', $booking->code), [
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
        $admin = $this->actingAsAdmin();
        $booking = Booking::factory()->pending()->create();

        $response = $this->post(route('admin.pesanan.update', $booking->code), [
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

    public static function transitionProvider()
    {
        $statuses = ['pending', 'paid', 'rented', 'returned', 'cancelled', 'expired'];
        $validTransitions = [
            'pending' => ['paid', 'expired', 'cancelled'],
            'paid' => ['rented', 'cancelled'],
            'rented' => ['returned'],
            'returned' => [],
            'cancelled' => [],
            'expired' => [],
        ];

        $data = [];
        foreach ($statuses as $from) {
            foreach ($statuses as $to) {
                $isValid = in_array($to, $validTransitions[$from]);
                $data["{$from} to {$to}"] = [$from, $to, $isValid];
            }
        }

        return $data;
    }

    #[DataProvider('transitionProvider')]
    public function test_semua_transisi_status($from, $to, $isValid)
    {
        $booking = Booking::factory()->create(['status' => $from]);
        $admin = $this->actingAsAdmin();

        $payload = [
            'to' => $to,
            'note' => 'catatan tes',
        ];

        if ($to === 'cancelled') {
            $payload['reason'] = 'Alasan tes';
        }

        $response = $this->post(route('admin.pesanan.update', $booking->code), $payload);

        if ($isValid) {
            $response->assertSessionHas('success');
            $this->assertDatabaseHas('bookings', [
                'id' => $booking->id,
                'status' => $to,
            ]);
            $this->assertDatabaseHas('booking_status_histories', [
                'booking_id' => $booking->id,
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $admin->id,
            ]);
        } else {
            $response->assertSessionHas('error');
            $this->assertDatabaseHas('bookings', [
                'id' => $booking->id,
                'status' => $from,
            ]);
            $this->assertDatabaseMissing('booking_status_histories', [
                'booking_id' => $booking->id,
                'to_status' => $to,
            ]);
        }
    }
}
