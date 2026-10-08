<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PengaturanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    private function getAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'brand_name' => 'Toko Mantap',
            'contact_whatsapp' => '6281122334455',
            'booking_max_days' => 10,
            'booking_invoice_minutes' => 120,
            'booking_buffer_minutes' => 30,
        ], $overrides);
    }

    public function test_admin_dapat_melihat_halaman_pengaturan(): void
    {
        $admin = $this->getAdmin();

        $this->actingAs($admin)->get(route('admin.pengaturan.edit'))->assertOk();
    }

    public function test_update_pengaturan_tersimpan_dan_mereset_cache(): void
    {
        Storage::fake('public');
        $admin = $this->getAdmin();

        // Isi dulu cache settings agar membuktikan Cache::forget berjalan
        $this->assertEquals('EduwGo', setting('brand.name', 'EduwGo'));

        $response = $this->actingAs($admin)->put(route('admin.pengaturan.update'), [
            'brand_name' => 'Toko Mantap',
            'contact_whatsapp' => '6281122334455',
            'booking_max_days' => 10,
            'booking_invoice_minutes' => 120,
            'booking_buffer_minutes' => 30,
            'brand_primary_color' => '#1e3a8a',
            'brand_logo_light' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('Toko Mantap', setting('brand.name'));
        $this->assertEquals('6281122334455', setting('contact.whatsapp'));
        $this->assertEquals(10, setting('booking.max_days'));

        // Cek file storage
        $this->assertTrue(str_starts_with(setting('brand.logo_light'), 'storage/settings/'));
    }

    public function test_ubah_brand_name_terlihat_di_title_navbar_dan_footer(): void
    {
        $admin = $this->getAdmin();

        $this->actingAs($admin)
            ->put(route('admin.pengaturan.update'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHas('success');

        // Muncul berurutan: title (head), navbar, footer
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Toko Mantap', 'Toko Mantap', 'Toko Mantap']);
    }

    public function test_ubah_warna_ter_render_sebagai_override_css(): void
    {
        $admin = $this->getAdmin();

        // Tanpa warna kustom → tidak ada blok override di HTML
        $this->get('/')->assertOk()->assertDontSee('--navy-900:', false);

        $this->actingAs($admin)
            ->put(route('admin.pengaturan.update'), $this->validPayload([
                'brand_primary_color' => '#123456',
                'brand_accent_color' => '#654321',
            ]))
            ->assertRedirect();

        $this->get('/')
            ->assertOk()
            ->assertSee('--navy-900: #123456', false)
            ->assertSee('--orange-500: #654321', false);
    }

    public function test_ubah_buffer_dipakai_oleh_availability_service(): void
    {
        $admin = $this->getAdmin();
        $booking = Booking::factory()->paid()->create([
            'start_at' => '2026-11-10 10:00:00',
            'end_at' => '2026-11-10 12:00:00',
        ]);
        $availability = app(AvailabilityService::class);
        $start = Carbon::parse('2026-11-10 12:00:00');

        // Buffer default 60 menit → mulai tepat saat booking berakhir masih terblokir
        $this->assertFalse($availability->isAvailable($booking->vehicle, $start, 1));

        $this->actingAs($admin)
            ->put(route('admin.pengaturan.update'), $this->validPayload(['booking_buffer_minutes' => 0]))
            ->assertRedirect();

        // Buffer 0 → tersedia langsung setelah booking lama berakhir
        $this->assertTrue($availability->isAvailable($booking->vehicle, $start, 1));
    }

    public function test_validasi_ditolak_jika_format_salah(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->put(route('admin.pengaturan.update'), [
            'brand_name' => 'Toko',
            'contact_whatsapp' => '0812345678', // Salah, harus 62
            'booking_max_days' => 15, // Max 14
            'booking_invoice_minutes' => 120,
            'booking_buffer_minutes' => 30,
            'brand_primary_color' => 'bukan-hex',
        ]);

        $response->assertSessionHasErrors(['contact_whatsapp', 'booking_max_days', 'brand_primary_color']);
    }

    public function test_user_biasa_dan_guest_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.pengaturan.edit'))->assertForbidden();
        $this->actingAs($user)->put(route('admin.pengaturan.update'))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.pengaturan.edit'))->assertRedirect(route('login'));
    }
}
