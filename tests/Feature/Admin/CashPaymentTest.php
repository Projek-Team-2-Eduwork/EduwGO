<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function getAdmin()
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_bayar_tunai_mengubah_status_dan_history()
    {
        $admin = $this->getAdmin();
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending, 'code' => 'EG.400001', 'total_amount' => 300000]);

        $paymentLama = Payment::create([
            'booking_id' => $booking->id, 'method' => 'xendit_invoice', 'status' => 'pending', 'amount' => 300000, 'gateway_reference' => 'inv_cash_1',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pesanan.pay-cash', $booking->code), [
            'amount' => 300000,
            'note' => 'Diterima di kasir',
        ]);

        $response->assertRedirect(route('admin.pesanan.show', $booking->code));

        $this->assertEquals(BookingStatus::Paid->value, $booking->fresh()->status->value);

        // Memastikan payment tunai terbuat
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'method' => 'cash',
            'status' => 'paid',
            'amount' => 300000,
        ]);

        // Memastikan payment lama xendit tidak dihapus/dirubah status paid
        $this->assertEquals('pending', $paymentLama->fresh()->status);

        // Cek History
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'to_status' => 'paid',
        ]);

        $history = $booking->histories()->latest()->first();
        $this->assertStringContainsString('Bayar tunai', $history->note);
    }

    public function test_webhook_expired_setelah_bayar_tunai_tidak_mengubah_apa_pun()
    {
        $admin = $this->getAdmin();
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending, 'code' => 'EG.400002', 'total_amount' => 300000]);

        Payment::create([
            'booking_id' => $booking->id, 'method' => 'xendit_invoice', 'status' => 'pending', 'amount' => 300000, 'gateway_reference' => 'inv_cash_2',
        ]);

        // Eksekusi pay-cash
        $this->actingAs($admin)->post(route('admin.pesanan.pay-cash', $booking->code), ['amount' => 300000]);

        // Simulasi webhook EXPIRED
        config(['services.xendit.callback_token' => 'token-rahasia']);

        $response = $this->postJson('/webhooks/xendit', [
            'id' => 'inv_cash_2',
            'external_id' => $booking->code,
            'status' => 'EXPIRED',
        ], ['x-callback-token' => 'token-rahasia']);

        $response->assertStatus(200);

        // Booking status harus tetap Paid, bukan Expired
        $this->assertEquals(BookingStatus::Paid->value, $booking->fresh()->status->value);
    }
}
