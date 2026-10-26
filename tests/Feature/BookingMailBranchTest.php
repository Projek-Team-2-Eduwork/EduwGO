<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Mail\BookingReminderMail;
use App\Mail\BookingStatusMail;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\VerifyEmailCode;
use App\Services\XenditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingMailBranchTest extends TestCase
{
    use RefreshDatabase;

    private function bookingAt(Branch $branch, BookingStatus $status = BookingStatus::Paid): Booking
    {
        return Booking::factory()->create([
            'status' => $status,
            'vehicle_id' => Vehicle::factory()->create(['branch_id' => $branch->id])->id,
        ]);
    }

    public function test_email_memakai_alamat_cabang_tempat_booking(): void
    {
        $semarang = Branch::factory()->create(['name' => 'Cabang Tembalang', 'address' => 'Jl. Banjarsari 1', 'operating_hours' => '07.00 - 21.00', 'phone' => '081111111111']);
        $jogja = Branch::factory()->create(['name' => 'Cabang Gejayan', 'address' => 'Jl. Affandi 9', 'operating_hours' => '09.00 - 17.00', 'phone' => '082222222222']);

        $html = (new BookingStatusMail($this->bookingAt($semarang), 'Pembayaran diterima'))->render();

        $this->assertStringContainsString('Lokasi ambil', $html);
        $this->assertStringContainsString('Jl. Banjarsari 1', $html);
        $this->assertStringContainsString('07.00 - 21.00', $html);
        $this->assertStringContainsString('https://wa.me/6281111111111', $html);
        $this->assertStringContainsString('google.com/maps/search', $html);
        $this->assertStringNotContainsString('Jl. Affandi 9', $html);

        $reminder = (new BookingReminderMail($this->bookingAt($jogja), 'pickup'))->render();

        $this->assertStringContainsString('Cabang Gejayan', $reminder);
        $this->assertStringContainsString('Jl. Affandi 9', $reminder);
        $this->assertStringContainsString('09.00 - 17.00', $reminder);
        $this->assertStringNotContainsString('Jl. Banjarsari 1', $reminder);
    }

    public function test_cabang_tanpa_alamat_jatuh_ke_global_lalu_disembunyikan(): void
    {
        $branch = Branch::factory()->create(['name' => 'Cabang Baru', 'address' => null, 'operating_hours' => null, 'phone' => null]);
        $booking = $this->bookingAt($branch);

        $html = (new BookingStatusMail($booking, 'Pembayaran diterima'))->render();

        $this->assertStringContainsString('Cabang Baru', $html);
        $this->assertStringNotContainsString('Alamat belum diatur', $html);
        $this->assertStringNotContainsString('Jam operasional', $html);
        $this->assertStringNotContainsString('google.com/maps', $html);

        Setting::create(['key' => 'contact.address', 'value' => 'Kantor Pusat 5']);
        Setting::create(['key' => 'contact.hours', 'value' => 'Senin - Jumat']);

        $html = (new BookingStatusMail($booking->fresh(), 'Pembayaran diterima'))->render();

        $this->assertStringContainsString('Kantor Pusat 5', $html);
        $this->assertStringContainsString('Jam operasional: Senin - Jumat', $html);
    }

    public function test_teks_status_cod_dan_semua_status_terisi(): void
    {
        $branch = Branch::factory()->create();
        $cod = $this->bookingAt($branch, BookingStatus::Pending);
        Payment::factory()->create(['booking_id' => $cod->id, 'method' => 'cash', 'status' => 'pending', 'payment_channel' => XenditService::COD]);

        $this->assertStringContainsString('bayar di tempat (COD)', (new BookingStatusMail($cod, 'Selesaikan pembayaran'))->render());

        foreach ([BookingStatus::Rented, BookingStatus::Returned, BookingStatus::Cancelled, BookingStatus::Expired] as $status) {
            $html = (new BookingStatusMail($this->bookingAt($branch, $status), 'Status'))->render();
            $this->assertStringContainsString(e($status->description()), $html);
        }
    }

    public function test_email_verifikasi_pakai_logo_dan_kontak_global(): void
    {
        Setting::create(['key' => 'contact.address', 'value' => 'Kantor Pusat 5']);

        $html = (string) (new VerifyEmailCode('123456'))->toMail(User::factory()->make(['name' => 'Budi']))->render();

        $this->assertStringContainsString('images/brand/lockup.png', $html);
        $this->assertStringContainsString('Kantor Pusat 5', $html);
        $this->assertStringContainsString('Salam,', $html);
        $this->assertStringNotContainsString('All rights reserved', $html);
    }
}
