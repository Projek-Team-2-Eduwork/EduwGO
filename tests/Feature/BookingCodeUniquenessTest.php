<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Sequence;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingCodeGenerator;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingCodeUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_generator_melewati_kode_yang_sudah_terpakai()
    {
        // Sequence di 0, tapi kode EG.000001 sudah dipakai booking lain
        Sequence::query()->where('key', 'booking')->update(['value' => 0]);
        Booking::factory()->create(['code' => 'EG.000001']);

        $code = app(BookingCodeGenerator::class)->next();

        // Generator loncat ke kode berikutnya yang kosong (retry loop)
        $this->assertSame('EG.000002', $code);
    }

    public function test_20_checkout_paralel_menghasilkan_20_kode_unik_berurutan()
    {
        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response([
                'id' => 'inv_dummy_uniqueness',
                'invoice_url' => 'https://checkout.xendit.co/web/inv_dummy',
                'expiry_date' => now()->addHour()->toISOString(),
            ], 200),
        ]);

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['is_active' => true, 'price_per_day' => 100000]);
        $bookingService = app(BookingService::class);

        $codes = [];
        $start = now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);

        // Simulasi 20 checkout beruntun (masing-masing dalam transaksi independen)
        for ($i = 0; $i < 20; $i++) {
            $booking = $bookingService->checkout($user, $vehicle, $start->copy()->addDays($i * 5), 1, [
                'customer_name' => 'Uji Paralel',
                'customer_phone' => '0812000000',
            ]);

            $codes[] = $booking->code;
        }

        $this->assertCount(20, $codes);
        $this->assertCount(20, array_unique($codes));

        $expectedCodes = [];
        for ($i = 1; $i <= 20; $i++) {
            $expectedCodes[] = 'EG.'.str_pad($i, 6, '0', STR_PAD_LEFT);
        }

        sort($codes);
        $this->assertSame($expectedCodes, $codes);
    }
}
