<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PembayaranTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-rahasia';

    private const QR_STRING = '00020101021126610014COM.GO-JEK.WWW0118936009143250602202020303UMI04091234567890503039990609123456789';

    protected $user;

    protected $booking;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.xendit.callback_token' => self::TOKEN,
            'services.xendit.secret_key' => 'xnd_development_fake',
        ]);

        $this->user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $this->booking = Booking::factory()->pending()->create([
            'code' => 'BOOK-PBR-001',
            'user_id' => $this->user->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => now()->addDays(1),
            'end_at' => now()->addDays(3),
            'duration_days' => 2,
            'total_amount' => 150000,
        ]);
    }

    private function fakeQrisResponse(): array
    {
        return [
            'payment_request_id' => 'pr-qris-123',
            'reference_id' => 'BOOK-PBR-001',
            'status' => 'REQUIRES_ACTION',
            'channel_code' => 'QRIS',
            'channel_properties' => [
                'expires_at' => now()->addHour()->toIso8601String(),
            ],
            'actions' => [
                ['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => self::QR_STRING],
            ],
        ];
    }

    private function fakeVaResponse(): array
    {
        return [
            'payment_request_id' => 'pr-va-123',
            'reference_id' => 'BOOK-PBR-001',
            'status' => 'REQUIRES_ACTION',
            'channel_code' => 'BCA_VIRTUAL_ACCOUNT',
            'channel_properties' => [
                'display_name' => 'PT EDUWGO INDONESIA',
                'expires_at' => now()->addHour()->toIso8601String(),
            ],
            'actions' => [
                ['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'VIRTUAL_ACCOUNT_NUMBER', 'value' => '887766554412'],
            ],
        ];
    }

    private function seedQrisPayment(): Payment
    {
        return Payment::create([
            'booking_id' => $this->booking->id,
            'method' => 'xendit_payment_request',
            'status' => 'pending',
            'gateway_reference' => 'pr-qris-123',
            'payment_channel' => 'QRIS',
            'instructions' => [
                'Scan QRIS dengan aplikasi bank atau e-wallet apa pun.',
                self::QR_STRING,
            ],
            'expires_at' => now()->addHour(),
            'amount' => 150000,
        ]);
    }

    public function test_tamu_dialihkan_ke_login()
    {
        $this->get('/pembayaran/BOOK-PBR-001')->assertRedirect(route('login'));
    }

    public function test_non_pemilik_ditolak_dan_qr_tidak_bocor()
    {
        $this->seedQrisPayment();

        $response = $this->actingAs(User::factory()->create())
            ->get('/pembayaran/BOOK-PBR-001');

        $response->assertForbidden();
        $response->assertDontSee(self::QR_STRING);
    }

    public function test_booking_non_pending_redirect()
    {
        $this->seedQrisPayment();
        $this->booking->update(['status' => BookingStatus::Paid]);

        $this->actingAs($this->user)
            ->get('/pembayaran/BOOK-PBR-001')
            ->assertRedirect(route('booking.show', $this->booking->code));

        // Halaman detail menampilkan notifikasi sukses (sekali baca)
        $this->actingAs($this->user)
            ->get(route('booking.show', $this->booking->code))
            ->assertOk()
            ->assertSee('Pembayaran berhasil!');
    }

    public function test_pembukaan_pertama_membuat_qris_dan_menampilkan_instruksi()
    {
        Http::fake(['api.xendit.co/*' => Http::response($this->fakeQrisResponse())]);

        $response = $this->actingAs($this->user)->get('/pembayaran/BOOK-PBR-001');

        $response->assertOk();
        $response->assertSee('Scan QRIS dengan aplikasi bank atau e-wallet apa pun.');
        $response->assertSee(self::QR_STRING);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v3/payment_requests')
            && $request['channel_code'] === 'QRIS'
            && $request['type'] === 'PAY');

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'method' => 'xendit_payment_request',
            'payment_channel' => 'QRIS',
            'status' => 'pending',
        ]);
    }

    public function test_pilih_metode_va_menampilkan_nomor_rekening()
    {
        Http::fake(['api.xendit.co/*' => Http::response($this->fakeVaResponse())]);

        $response = $this->actingAs($this->user)
            ->get('/pembayaran/BOOK-PBR-001?metode=BANK_TRANSFER');

        $response->assertOk();
        $response->assertSee('887766554412');
        $response->assertSee('PT EDUWGO INDONESIA');

        Http::assertSent(fn ($request) => $request['channel_code'] === 'BCA_VIRTUAL_ACCOUNT');

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'payment_channel' => 'BCA_VIRTUAL_ACCOUNT',
            'gateway_reference' => 'pr-va-123',
        ]);
    }

    public function test_pindah_metode_membuat_payment_request_baru()
    {
        // QRIS pending sudah ada — memilih VA harus membuat payment request VA baru
        $this->seedQrisPayment();
        Http::fake(['api.xendit.co/*' => Http::response($this->fakeVaResponse())]);

        $this->actingAs($this->user)
            ->get('/pembayaran/BOOK-PBR-001?metode=BANK_TRANSFER')
            ->assertOk()
            ->assertSee('887766554412');

        Http::assertSentCount(1);
        // ?metode=BANK_TRANSFER (bucket lawas) dipetakan ke channel_code BCA_VIRTUAL_ACCOUNT
        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'payment_channel' => 'QRIS',
        ]);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'payment_channel' => 'BCA_VIRTUAL_ACCOUNT',
        ]);
        $this->assertSame(2, Payment::where('booking_id', $this->booking->id)->count());
    }

    public function test_polling_selaraskan_status_via_api_ketika_webhook_belum_masuk()
    {
        // Booking masih pending, webhook test gak pernah nyampe ke localhost.
        // Polling "Saya Sudah Bayar" harus cek langsung ke API Xendit (EG-56).
        config(['services.xendit.mode' => 'payment_request']);
        $payment = $this->seedQrisPayment();

        Http::fake([
            'api.xendit.co/v3/payment_requests/*' => Http::response([
                'payment_request_id' => $payment->gateway_reference,
                'status' => 'SUCCEEDED',
                'succeeded_at' => now()->toIso8601String(),
            ]),
        ]);

        $this->actingAs($this->user)
            ->get(route('booking.status', $this->booking->code))
            ->assertOk()
            ->assertJson(['status' => 'paid', 'paid' => true]);

        // API Xendit disentuh, lalu status lokal ke-deteksi walau webhook gak masuk
        Http::assertSent(fn ($request) => str_contains($request->url(), '/v3/payment_requests/'.$payment->gateway_reference));
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(BookingStatus::Paid, $this->booking->fresh()->status);
    }

    public function test_polling_belum_bayar_biarkan_pending_ketika_api_balas_requires_action()
    {
        config(['services.xendit.mode' => 'payment_request']);
        $this->seedQrisPayment();

        Http::fake([
            'api.xendit.co/v3/payment_requests/*' => Http::response([
                'payment_request_id' => 'pr-qris-123',
                'status' => 'REQUIRES_ACTION',
            ]),
        ]);

        $this->actingAs($this->user)
            ->get(route('booking.status', $this->booking->code))
            ->assertOk()
            ->assertJson(['status' => 'pending', 'paid' => false]);
    }

    public function test_polling_tetap_pending_ketika_cek_api_gagal()
    {
        // Cek API gagal (mis. key bermasalah) bukan fatal — webhook tetap sumber kebenaran
        config(['services.xendit.mode' => 'payment_request']);
        $this->seedQrisPayment();

        Http::fake([
            'api.xendit.co/v3/payment_requests/*' => Http::response(['message' => 'server error'], 500),
        ]);

        $this->actingAs($this->user)
            ->get(route('booking.status', $this->booking->code))
            ->assertOk()
            ->assertJson(['status' => 'pending', 'paid' => false]);
    }

    public function test_webhook_referensi_tidak_dikenal_dianggap_ok_agar_xendit_tidak_mengulang()
    {
        // Ping uji dashboard Xendit & event lama: token valid tapi referensi tak
        // dikenal → di-acknowledge 200 (EG-57), bukan 404 yang bikin Xendit retry
        $this->seedQrisPayment();

        $this->postJson('/webhooks/xendit', [
            'event' => 'payment.capture',
            'data' => ['payment_request_id' => 'pr-tidak-ada', 'status' => 'SUCCEEDED'],
        ], ['x-callback-token' => self::TOKEN])->assertOk();

        // Event invoice versi lama dengan id tak dikenal juga di-acknowledge
        $this->postJson('/webhooks/xendit', [
            'event' => 'invoice.status',
            'id' => 'inv-tidak-ada',
            'status' => 'PAID',
        ], ['x-callback-token' => self::TOKEN])->assertOk();

        // Payment asli tidak tersentuh oleh event tak dikenal
        $this->assertSame('pending', $this->booking->fresh()->status->value);
        $this->assertSame('pending', Payment::where('booking_id', $this->booking->id)->first()->status);

        // Token tetap wajib — request tanpa/misi token tetap ditolak
        $this->postJson('/webhooks/xendit', [
            'event' => 'payment.capture',
            'data' => ['payment_request_id' => 'pr-tidak-ada'],
        ], ['x-callback-token' => 'token-salah'])->assertForbidden();

        $this->postJson('/webhooks/xendit', [
            'event' => 'payment.capture',
            'data' => ['payment_request_id' => 'pr-tidak-ada'],
        ])->assertForbidden();
    }

    public function test_polling_setelah_webhook_paid_mengalihkan_ke_berhasil()
    {
        $this->seedQrisPayment();

        $this->postJson('/webhooks/xendit', [
            'event' => 'payment.capture',
            'data' => [
                'payment_request_id' => 'pr-qris-123',
                'status' => 'SUCCEEDED',
                'captures' => [['capture_timestamp' => '2026-10-13T10:00:00.000Z']],
            ],
        ], ['x-callback-token' => self::TOKEN])->assertOk();

        // Route polling yang dipakai tombol "Saya Sudah Bayar" — menyiapkan
        // notifikasi sekali-baca untuk halaman detail pesanan
        $statusResponse = $this->actingAs($this->user)
            ->get(route('booking.status', $this->booking->code));

        $statusResponse->assertOk()
            ->assertJson(['status' => 'paid', 'paid' => true])
            ->assertSessionHas('bayar_berhasil');

        // Halaman bayar mengalihkan otomatis ke halaman detail pesanan
        $this->actingAs($this->user)
            ->get('/pembayaran/BOOK-PBR-001')
            ->assertRedirect(route('booking.show', $this->booking->code));
    }

    public function test_halaman_detail_setelah_bayar_menampilkan_langkah_selanjutnya()
    {
        $this->booking->update(['status' => BookingStatus::Paid]);

        $this->actingAs($this->user)
            ->get(route('booking.show', $this->booking->code))
            ->assertOk()
            ->assertSee('Langkah Selanjutnya')
            ->assertSee('KTP atau SIM');
    }

    public function test_api_gagal_menampilkan_pesan_error_dan_retry()
    {
        Http::fake(['api.xendit.co/*' => Http::response(['message' => 'server error'], 500)]);

        $response = $this->actingAs($this->user)->get('/pembayaran/BOOK-PBR-001');

        $response->assertOk();
        $response->assertSee('Gagal menghubungi penyedia pembayaran');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_instruksi_kosong_menampilkan_empty_state()
    {
        Payment::create([
            'booking_id' => $this->booking->id,
            'method' => 'xendit_payment_request',
            'status' => 'pending',
            'gateway_reference' => 'pr-qris-456',
            'payment_channel' => 'QRIS',
            'instructions' => null,
            'expires_at' => now()->addHour(),
            'amount' => 150000,
        ]);

        $this->actingAs($this->user)
            ->get('/pembayaran/BOOK-PBR-001')
            ->assertOk()
            ->assertSee('Metode pembayaran belum tersedia');
    }

    public function test_halaman_bayar_menampilkan_katalog_channel_per_kategori()
    {
        Http::fake(['api.xendit.co/*' => Http::response($this->fakeQrisResponse())]);

        $this->actingAs($this->user)
            ->get('/pembayaran/BOOK-PBR-001')
            ->assertOk()
            ->assertSee('Pilih Metode Pembayaran')
            ->assertSee('Virtual Account')
            ->assertSee('Kartu Kredit')
            ->assertSee('E-Wallet')
            ->assertSee('QR')
            ->assertSee('Retail Outlet')
            ->assertSee('BCA Virtual Account')
            ->assertSee('ShopeePay')
            ->assertSee('QRIS')
            ->assertSee('Alfamart')
            ->assertSee('Indomaret');
    }

    public function test_pilih_metode_ovo_mengirim_nomor_hp_penyewa()
    {
        $this->user->update(['phone' => '081234567890']);

        Http::fake(['api.xendit.co/*' => Http::response([
            'payment_request_id' => 'pr-ovo-123',
            'reference_id' => 'BOOK-PBR-001',
            'status' => 'REQUIRES_ACTION',
            'channel_code' => 'OVO',
            'actions' => [['type' => 'DEEPLINK_URL', 'value' => 'https://shopeepay.example/abc']],
            'channel_properties' => ['expires_at' => now()->addHour()->toIso8601String()],
        ])]);

        $this->actingAs($this->user)
            ->get('/pembayaran/BOOK-PBR-001?metode=OVO')
            ->assertOk()
            ->assertSee('OVO');

        Http::assertSent(fn ($request) => $request['channel_code'] === 'OVO'
            && $request['channel_properties']['account_mobile_number'] === '+6281234567890');

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'payment_channel' => 'OVO',
        ]);
    }

    public function test_pilih_metode_retail_menampilkan_kode_pembayaran()
    {
        Http::fake(['api.xendit.co/*' => Http::response([
            'payment_request_id' => 'pr-alfamart-123',
            'reference_id' => 'BOOK-PBR-001',
            'status' => 'REQUIRES_ACTION',
            'channel_code' => 'ALFAMART',
            'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'PAYMENT_CODE', 'value' => 'TESTCODE123']],
            'channel_properties' => ['expires_at' => now()->addHour()->toIso8601String()],
        ])]);

        $this->actingAs($this->user)
            ->get('/pembayaran/BOOK-PBR-001?metode=ALFAMART')
            ->assertOk()
            ->assertSee('Kode pembayaran: TESTCODE123');

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'payment_channel' => 'ALFAMART',
        ]);
    }
}
