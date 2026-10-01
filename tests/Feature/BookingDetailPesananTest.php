<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingDetailPesananTest extends TestCase
{
    use RefreshDatabase;

    public function test_pemilik_bisa_melihat_halaman_detail_dan_kode_booking()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'start_at' => now(),
            'duration_days' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('booking.show', $booking->code));
        
        $response->assertOk();
        $response->assertSee($booking->code);
    }

    public function test_user_lain_mendapat_error_403()
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $owner->id,
            'start_at' => now(),
            'duration_days' => 1,
        ]);

        $response = $this->actingAs($attacker)->get(route('booking.show', $booking->code));
        
        $response->assertForbidden();
    }

    public function test_status_pending_menampilkan_tombol_bayar_dan_batalkan()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Pending,
            'start_at' => now(),
            'duration_days' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('booking.show', $booking->code));
        
        $response->assertOk();
        $response->assertSee(route('booking.waiting', $booking->code));
        $response->assertSee('Batalkan');
    }

    public function test_status_paid_tidak_menampilkan_tombol_bayar_atau_batal()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Paid,
            'start_at' => now(),
            'duration_days' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('booking.show', $booking->code));
        
        $response->assertOk();
        $response->assertDontSee(route('booking.waiting', $booking->code));
        $response->assertDontSee('Batalkan pesanan');
        $response->assertSee('Chat Admin');
    }

    public function test_banner_overdue_tampil_jika_rented_dan_lewat_batas_waktu()
    {
        $user = User::factory()->create();
        
        $bookingOverdue = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Rented,
            'start_at' => now()->subDays(2),
            'end_at' => now()->subHour(),
            'duration_days' => 1,
        ]);

        $responseOverdue = $this->actingAs($user)->get(route('booking.show', $bookingOverdue->code));
        $responseOverdue->assertSee('Waktu sewa sudah lewat, segera kembalikan unit');

        $bookingNormal = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Rented,
            'start_at' => now(),
            'end_at' => now()->addDays(1),
            'duration_days' => 1,
        ]);

        $responseNormal = $this->actingAs($user)->get(route('booking.show', $bookingNormal->code));
        $responseNormal->assertDontSee('Waktu sewa sudah lewat, segera kembalikan unit');
    }

    public function test_timeline_menampilkan_label_sistem_dan_catatan()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Pending,
            'start_at' => now(),
            'duration_days' => 1,
        ]);

        $booking->histories()->create([
            'from_status' => null,
            'to_status' => BookingStatus::Pending->value,
            'changed_by' => null,
            'note' => 'Dibuat otomatis oleh worker',
        ]);

        $response = $this->actingAs($user)->get(route('booking.show', $booking->code));
        
        $response->assertOk();
        $response->assertSee('Sistem');
        $response->assertSee('Dibuat otomatis oleh worker');
    }

    public function test_tombol_chat_admin_tampil_untuk_semua_status()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Cancelled,
            'start_at' => now(),
            'duration_days' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('booking.show', $booking->code));
        
        $response->assertOk();
        $response->assertSee('Chat Admin');
    }
}