<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UiReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_lokasi_ambil_di_detail_checkout_dan_sukses_mengikuti_motor(): void
    {
        $branch = Branch::factory()->create(['name' => 'Cabang Pengambilan', 'address' => 'Jl. Pengambilan 98', 'operating_hours' => '09.00–18.00 WIB', 'phone' => '081234567891']);
        $vehicle = Vehicle::factory()->create(['branch_id' => $branch->id]);
        $user = User::factory()->create();
        $booking = Booking::factory()->paid()->create(['user_id' => $user->id, 'vehicle_id' => $vehicle->id]);

        foreach ([route('booking.show', $booking->code), route('booking.success', $booking->code), route('checkout.show', ['vehicle' => $vehicle, 'start' => now()->addWeek()->toIso8601String(), 'days' => 1])] as $url) {
            $html = $this->actingAs($user)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('Lokasi pengambilan', $html);
            $this->assertStringContainsString($branch->address, $html);
            $this->assertStringContainsString($branch->operating_hours, $html);
            $this->assertStringContainsString('maps/search/?api=1', $html);
            $this->assertStringContainsString('wa.me/6281234567891', $html);
        }
    }

    public function test_detail_penyewa_tidak_membocorkan_gateway_dan_hanya_satu_cta_bayar(): void
    {
        $booking = Booking::factory()->pending()->create();
        Payment::factory()->create(['booking_id' => $booking->id, 'gateway_reference' => 'secret-gateway-reference', 'status' => 'pending']);
        $html = $this->actingAs($booking->user)->get(route('booking.show', $booking->code))->assertOk()->getContent();

        $this->assertStringNotContainsString('secret-gateway-reference', $html);
        $this->assertStringNotContainsString('Xendit', $html);
        $this->assertStringNotContainsString('Biaya tambahan', $html);
        $this->assertSame(1, substr_count($html, '>Lanjut bayar</a>'));
    }

    public function test_tab_pesanan_memisahkan_status_dan_tetap_milik_pengguna(): void
    {
        $user = User::factory()->create();
        $pending = Booking::factory()->pending()->create(['user_id' => $user->id]);
        $paid = Booking::factory()->paid()->create(['user_id' => $user->id]);
        $returned = Booking::factory()->returned()->create(['user_id' => $user->id]);
        $other = Booking::factory()->pending()->create();

        $this->actingAs($user)->get(route('booking.index', ['tab' => 'menunggu']))->assertOk()->assertSee($pending->code)->assertDontSee($paid->code)->assertDontSee($returned->code)->assertDontSee($other->code);
        $this->get(route('booking.index', ['tab' => 'selesai']))->assertOk()->assertSee($returned->code)->assertDontSee($pending->code)->assertDontSee($paid->code);
    }

    public function test_harga_hero_minimum_cabang_aktif_dan_status_mendatang_tidak_memblokir_link(): void
    {
        $branch = Branch::factory()->create(['slug' => 'pilihan']);
        $vehicle = Vehicle::factory()->create(['branch_id' => $branch->id, 'price_per_day' => 65000]);
        Vehicle::factory()->create(['branch_id' => $branch->id, 'price_per_day' => 1000, 'is_active' => false]);
        Vehicle::factory()->create(['price_per_day' => 2000]);
        Booking::factory()->paid()->create(['vehicle_id' => $vehicle->id, 'start_at' => now()->addDays(2), 'end_at' => now()->addDays(3)]);
        $html = $this->withCookie('branch', $branch->slug)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Rp65.000', $html);
        $this->assertStringContainsString('Pilih jadwal untuk cek', $html);
        $this->assertStringContainsString('Dipesan berikutnya', $html);
        $card = Blade::render('<x-vehicle-card :vehicle="$vehicle" :available="false" />', ['vehicle' => $vehicle]);
        $this->assertSame(1, substr_count($card, '<a '));
        $this->assertStringContainsString('Lihat detail', $card);
        $this->assertStringNotContainsString('aria-disabled', $card);
    }

    public function test_validasi_dan_field_memakai_bahasa_indonesia(): void
    {
        app()->setLocale('id');
        $messages = Validator::make([], ['vehicle_type_id' => 'required', 'plate_number' => 'required'])->errors();
        $this->assertSame('Tipe kendaraan wajib diisi.', $messages->first('vehicle_type_id'));
        $this->assertSame('Nomor plat wajib diisi.', $messages->first('plate_number'));
        $this->assertStringContainsString('type="text"', Blade::render('<x-text-input name="nama" />'));
        $this->assertStringContainsString('Pilih foto', Blade::render('<x-form.file name="foto" />'));
    }

    public function test_halaman_kredit_foto_dan_aset_terpasang(): void
    {
        $this->get('/kredit-foto')->assertOk()->assertSee('CC BY-SA 4.0')->assertSee('Firzafp')->assertSee('diubah ukurannya');
        $this->get('/login')->assertOk()->assertSee('<title>Masuk — EduwGo</title>', false)->assertSee('favicon.svg')->assertSee('site.webmanifest')->assertSee('images/brand/og.png')->assertDontSee('auth-toko.webp')->assertDontSee('window.scrollTo');
        foreach (['public/favicon.ico', 'public/apple-touch-icon.png', 'public/images/home/hero-road.webp', 'public/images/home/hero-road-mobile.webp'] as $path) {
            $this->assertGreaterThan(0, filesize(base_path($path)));
            $this->assertLessThan(250 * 1024, filesize(base_path($path)));
        }
    }

    public function test_gagal_dibatalkan_memakai_pesan_yang_sesuai(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Cancelled]);
        $this->actingAs($booking->user)->get(route('booking.failed', $booking->code))->assertOk()->assertSee('Pesanan dibatalkan')->assertDontSee('Batas pembayaran terlewat');
    }
}
