<?php

namespace Tests\Feature\Security;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\XenditService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checkout: pesan error gateway tidak bocor ke pengguna dan request dibatasi per menit.
 */
class CheckoutHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function payload(int $offsetDays): array
    {
        return [
            'start' => now()->addDays($offsetDays)->toISOString(),
            'days' => 1,
            'customer_name' => 'Budi',
            'customer_phone' => '081234567890',
        ];
    }

    public function test_checkout_berhasil_saat_gateway_normal(): void
    {
        $this->mock(XenditService::class, fn ($mock) => $mock->shouldReceive('createInvoice')->andReturnUsing(
            fn ($booking) => Payment::factory()->create(['booking_id' => $booking->id, 'status' => 'pending']),
        ));

        $vehicle = Vehicle::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.store', $vehicle->id), $this->payload(2))
            ->assertRedirect(route('booking.waiting', Booking::firstOrFail()->code));
    }

    public function test_error_gateway_tidak_ditampilkan_ke_pengguna(): void
    {
        $this->mock(XenditService::class, fn ($mock) => $mock->shouldReceive('createInvoice')
            ->andThrow(new Exception('Gagal membuat invoice Xendit: {"error_code":"INVALID_API_KEY","payer_email":"rahasia@example.com"}')));

        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->from(route('kendaraan.detail', $vehicle))
            ->post(route('checkout.store', $vehicle->id), $this->payload(2));

        $response->assertRedirect(route('kendaraan.detail', $vehicle))
            ->assertSessionHas('error', 'Gagal memproses checkout. Silakan coba lagi atau hubungi admin.');
        $this->assertStringNotContainsString('INVALID_API_KEY', session('error'));
        $this->assertSame(0, Booking::count());
    }

    public function test_checkout_dibatasi_sepuluh_kali_per_menit(): void
    {
        $this->mock(XenditService::class, fn ($mock) => $mock->shouldReceive('createInvoice')->andReturnUsing(
            fn ($booking) => Payment::factory()->create(['booking_id' => $booking->id, 'status' => 'pending']),
        ));

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->post(route('checkout.store', $vehicle->id), $this->payload(2 + $i * 3))
                ->assertStatus(302);
        }

        $this->actingAs($user)->post(route('checkout.store', $vehicle->id), $this->payload(40))
            ->assertStatus(429);
    }
}
