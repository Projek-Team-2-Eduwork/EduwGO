<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverdueCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_command_menandai_satu_kali()
    {
        $booking = Booking::factory()->rented()->create([
            'end_at' => now()->subHour(),
        ]);

        $this->artisan('bookings:flag-overdue')->assertSuccessful();

        // Dijalankan 2 kali, seharusnya history tetap 1 untuk menghindari duplikasi flag
        $this->artisan('bookings:flag-overdue')->assertSuccessful();

        $this->assertEquals(1, $booking->histories()->where('note', 'Terlambat dikembalikan')->count());

        $history = $booking->histories()->where('note', 'Terlambat dikembalikan')->first();
        $this->assertEquals('rented', $history->from_status);
        $this->assertEquals('rented', $history->to_status);
        $this->assertNull($history->changed_by);

        // Memastikan status booking asli tetap rented
        $this->assertEquals('rented', $booking->fresh()->status->value);
    }

    public function test_booking_belum_lewat_tidak_ditandai()
    {
        Booking::factory()->rented()->create([
            'end_at' => now()->addDay(),
        ]);

        Booking::factory()->pending()->create([
            'end_at' => now()->subHour(),
        ]);

        $this->artisan('bookings:flag-overdue')->assertSuccessful();

        $this->assertDatabaseMissing('booking_status_histories', [
            'note' => 'Terlambat dikembalikan',
        ]);
    }

    public function test_filter_terlambat_admin()
    {
        $admin = $this->actingAsAdmin();

        $overdue = Booking::factory()->rented()->create([
            'code' => 'OVERDUE123',
            'end_at' => now()->subHour(),
        ]);

        $notOverdue = Booking::factory()->rented()->create([
            'code' => 'OKAY456',
            'end_at' => now()->addHour(),
        ]);

        $response = $this->get(route('admin.pesanan.index', ['status' => 'terlambat']));

        $response->assertOk();
        $response->assertSee('OVERDUE123');
        $response->assertDontSee('OKAY456');

        // Assert label diffForHumans muncul
        $response->assertSee('Terlambat '.$overdue->end_at->diffForHumans());
    }

    public function test_badge_dashboard()
    {
        $admin = $this->actingAsAdmin();

        // Tanpa overdue (assertDontSee)
        $response = $this->get(route('admin.dashboard'));
        $response->assertDontSee('unit terlambat');

        // Tambah overdue
        Booking::factory()->rented()->create([
            'end_at' => now()->subHour(),
        ]);

        // Cek jika muncul (assertSee)
        $responseWithOverdue = $this->get(route('admin.dashboard'));
        $responseWithOverdue->assertSee('unit terlambat');
        $responseWithOverdue->assertSee('status=terlambat');
    }
}
