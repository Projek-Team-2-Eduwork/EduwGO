<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_vehicles_are_real_motorcycles_with_local_photos(): void
    {
        $vehicles = Vehicle::query()->with('type')->get();

        $this->assertGreaterThanOrEqual(10, $vehicles->count());
        $this->assertSame($vehicles->count(), $vehicles->pluck('plate_number')->unique()->count());

        foreach (['Vario 125', 'Vario 160', 'Scoopy', 'Genio', 'BeAT', 'PCX 160', 'ADV 160', 'Forza', 'Stylo 160', 'Supra X 125'] as $model) {
            $this->assertTrue($vehicles->contains(fn (Vehicle $v) => str_ends_with($v->name, $model)), "motor {$model} tidak ada");
        }

        foreach ($vehicles as $vehicle) {
            $this->assertContains($vehicle->type->slug, ['matic', 'cub', 'sport']);
            $this->assertGreaterThanOrEqual(60000, $vehicle->price_per_day);
            $this->assertLessThanOrEqual(150000, $vehicle->price_per_day);
            $this->assertNotEmpty($vehicle->description);
            $this->assertFileExists(public_path($vehicle->image));
            $this->assertLessThanOrEqual(300 * 1024, filesize(public_path($vehicle->image)));
        }
    }

    public function test_bookings_have_expected_status_distribution_and_period(): void
    {
        $this->assertSame(30, Booking::count());

        $byStatus = Booking::query()->get()->countBy(fn (Booking $b) => $b->status->value)->all();

        $this->assertEquals(
            ['returned' => 18, 'rented' => 3, 'paid' => 3, 'pending' => 2, 'expired' => 3, 'cancelled' => 1],
            $byStatus,
        );

        $this->assertTrue(Booking::query()->where('start_at', '<', now()->subDays(61))->doesntExist());
        $this->assertTrue(Booking::query()->where('start_at', '>', now()->addDays(15))->doesntExist());
        $this->assertSame(30, Booking::query()->distinct()->count('code'));
        $this->assertSame(0, Booking::query()->where('code', 'not like', 'EG.______')->count());
    }

    public function test_no_two_active_bookings_overlap_on_same_vehicle_including_buffer(): void
    {
        $active = Booking::query()->active()->orderBy('start_at')->get()->groupBy('vehicle_id');

        foreach ($active as $bookings) {
            foreach ($bookings->values() as $i => $current) {
                $next = $bookings->values()->get($i + 1);

                if ($next === null) {
                    continue;
                }

                $this->assertGreaterThanOrEqual(
                    60,
                    $current->end_at->diffInMinutes($next->start_at, false),
                    "{$current->code} dan {$next->code} bentrok pada unit yang sama",
                );
            }
        }
    }

    public function test_seeded_schedule_is_rejected_by_availability_service(): void
    {
        $booking = Booking::query()->active()->with('vehicle')->firstOrFail();
        $availability = app(AvailabilityService::class);

        $this->assertFalse($availability->isAvailable($booking->vehicle, $booking->start_at->copy()->addHour(), 1));
        // Tepat di dalam buffer 60 menit setelah selesai sewa juga masih ditolak.
        $this->assertFalse($availability->isAvailable($booking->vehicle, $booking->end_at->copy()->addMinutes(30), 1));
    }

    public function test_histories_and_payments_follow_state_machine(): void
    {
        $bookings = Booking::query()->with(['histories' => fn ($q) => $q->orderBy('id'), 'payments'])->get();

        foreach ($bookings as $booking) {
            $histories = $booking->histories;

            $this->assertNull($histories->first()->from_status, $booking->code);
            $this->assertSame($booking->status->value, $histories->last()->to_status, $booking->code);

            foreach ($histories->skip(1)->values() as $i => $history) {
                $from = BookingStatus::from($history->from_status);

                $this->assertSame($histories[$i]->to_status, $from->value, $booking->code);
                $this->assertTrue($from->canTransitionTo(BookingStatus::from($history->to_status)), $booking->code);
            }

            $this->assertCount(1, $booking->payments, $booking->code);
            $payment = $booking->payments->first();

            $this->assertSame((float) $booking->total_amount, (float) $payment->amount);
            $this->assertSame(
                match ($booking->status) {
                    BookingStatus::Pending => 'pending',
                    BookingStatus::Expired => 'expired',
                    BookingStatus::Cancelled => 'failed',
                    default => 'paid',
                },
                $payment->status,
                $booking->code,
            );
            $this->assertSame($payment->status === 'paid', $payment->paid_at !== null, $booking->code);
        }

        // Pending masih dalam batas invoice; expired sudah lewat batas.
        $this->assertTrue(Booking::query()->where('status', 'pending')->get()
            ->every(fn (Booking $b) => $b->payments()->first()->expires_at->isFuture()));
        $this->assertTrue(Booking::query()->where('status', 'expired')->get()
            ->every(fn (Booking $b) => $b->payments()->first()->expires_at->isPast()));
    }

    public function test_demo_data_gives_dashboard_five_top_vehicles_and_non_zero_revenue(): void
    {
        $top = Booking::query()->selectRaw('vehicle_id, count(*) as total')
            ->groupBy('vehicle_id')->orderByDesc('total')->limit(5)->get();

        $this->assertCount(5, $top);
        $this->assertGreaterThan(0, Booking::query()->whereIn('status', ['paid', 'rented', 'returned'])->sum('total_amount'));
    }
}
