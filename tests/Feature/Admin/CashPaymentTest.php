<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function getAdmin()
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create(['branch_id' => $this->defaultBranch()->id]);
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

        // Payment Xendit yang masih pending ditutup (tidak bisa dibayar dobel), tidak dihapus
        $this->assertEquals('expired', $paymentLama->fresh()->status);

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

    public function test_bayar_tunai_nominal_tidak_sama_dengan_tagihan_ditolak()
    {
        $admin = $this->getAdmin();
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending, 'code' => 'EG.400003', 'total_amount' => 300000]);

        $this->actingAs($admin)->post(route('admin.pesanan.pay-cash', $booking->code), ['amount' => 1])
            ->assertSessionHasErrors(['amount' => 'Nominal tunai harus sama dengan total tagihan Rp 300.000.']);

        $this->assertEquals(BookingStatus::Pending, $booking->fresh()->status);
        $this->assertDatabaseMissing('payments', ['booking_id' => $booking->id, 'method' => 'cash']);
    }

    public function test_bayar_tunai_membatalkan_payment_pending_di_xendit()
    {
        config(['services.xendit.secret_key' => 'xnd_development_fake']);
        Http::fake(['api.xendit.co/*' => Http::response(['status' => 'CANCELED'])]);

        $admin = $this->getAdmin();
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending, 'code' => 'EG.400004', 'total_amount' => 300000]);
        $qris = Payment::create([
            'booking_id' => $booking->id, 'method' => 'xendit_payment_request', 'status' => 'pending', 'amount' => 300000, 'gateway_reference' => 'pr-cash-1',
        ]);

        $this->actingAs($admin)->post(route('admin.pesanan.pay-cash', $booking->code), ['amount' => 300000])->assertRedirect();

        $this->assertEquals('expired', $qris->fresh()->status);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.xendit.co/v3/payment_requests/pr-cash-1/cancel');
    }

    public function test_admin_tidak_bisa_set_lunas_lewat_form_status()
    {
        $admin = $this->getAdmin();
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending, 'code' => 'EG.400005', 'total_amount' => 300000]);

        $this->actingAs($admin)->post(route('admin.pesanan.update', $booking->code), ['to' => 'paid'])
            ->assertSessionHasErrors(['to' => 'Status Lunas hanya bisa diset lewat Catat Bayar Tunai atau pembayaran Xendit.']);

        $this->assertEquals(BookingStatus::Pending, $booking->fresh()->status);
    }
}
