<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CronEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-cron-rahasia';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.cron.token' => self::TOKEN]);
    }

    private function bookingPendingKedaluwarsa(): Booking
    {
        $booking = Booking::factory()->pending()->create(['created_at' => now()->subMinutes(65)]);
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'pending',
            'gateway_reference' => 'inv_cron_1',
            'expires_at' => now()->subMinutes(5),
        ]);

        return $booking;
    }

    public function test_token_benar_menjalankan_expire_booking()
    {
        $booking = $this->bookingPendingKedaluwarsa();

        $this->withToken(self::TOKEN)
            ->postJson('/cron/run', ['task' => 'bookings:expire'])
            ->assertOk()
            ->assertJson(['status' => 'ok', 'task' => 'bookings:expire']);

        $this->assertEquals(BookingStatus::Expired, $booking->fresh()->status);
    }

    public function test_tanpa_task_menjalankan_schedule_run()
    {
        $this->withToken(self::TOKEN)
            ->postJson('/cron/run')
            ->assertOk()
            ->assertJson(['status' => 'ok', 'task' => 'schedule:run']);
    }

    public function test_token_salah_ditolak()
    {
        $booking = $this->bookingPendingKedaluwarsa();

        $this->withToken('token-salah')
            ->postJson('/cron/run', ['task' => 'bookings:expire'])
            ->assertUnauthorized();

        $this->assertEquals(BookingStatus::Pending, $booking->fresh()->status);
    }

    public function test_tanpa_token_ditolak()
    {
        $this->postJson('/cron/run')->assertUnauthorized();
    }

    public function test_cron_token_belum_diset_selalu_ditolak()
    {
        config(['services.cron.token' => null]);

        $this->withToken('')
            ->postJson('/cron/run')
            ->assertUnauthorized();
    }

    public function test_task_di_luar_daftar_ditolak()
    {
        $this->withToken(self::TOKEN)
            ->postJson('/cron/run', ['task' => 'migrate:fresh'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('task');
    }
}
