<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCekStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_sinkronisasi_ke_paid_sukses()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'pending',
            'gateway_reference' => 'inv_999',
            'expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'https://api.xendit.co/v2/invoices/*' => Http::response([
                'id' => 'inv_999',
                'status' => 'PAID',
                'paid_at' => now()->toISOString(),
            ], 200),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pesanan.cek-status', $booking->code));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals(BookingStatus::Paid, $booking->status);

        $payment = $booking->latestPayment;
        $this->assertEquals('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'to_status' => BookingStatus::Paid->value,
        ]);
    }

    public function test_user_biasa_ditolak()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);

        $response = $this->actingAs($user)->get(route('admin.pesanan.cek-status', $booking->code));

        $response->assertForbidden();
    }

    public function test_halaman_show_tampil_dengan_tombol_cek_status()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'pending',
            'gateway_reference' => 'inv_888',
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pesanan.show', $booking->code));

        $response->assertOk();
        $response->assertSee('Cek Status Xendit');
    }

    public function test_tombol_tidak_tampil_jika_bukan_pending()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $booking = Booking::factory()->create(['status' => BookingStatus::Paid]);
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'paid',
            'gateway_reference' => 'inv_777',
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pesanan.show', $booking->code));

        $response->assertOk();
        $response->assertDontSee('Cek Status Xendit');
    }
}
