<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingDatabaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Happy path: schema sesuai SPEC bagian 7.
     */
    public function test_schema_tables_match_spec(): void
    {
        $this->assertTrue(Schema::hasColumns('bookings', [
            'code', 'user_id', 'vehicle_id', 'start_at', 'end_at', 'duration_days',
            'price_per_day', 'total_amount', 'status', 'customer_name',
            'customer_phone', 'notes', 'cancel_reason',
        ]), 'kolom bookings tidak sesuai spesifikasi');

        $this->assertTrue(Schema::hasColumns('booking_status_histories', [
            'booking_id', 'from_status', 'to_status', 'changed_by', 'note',
        ]));

        $this->assertTrue(Schema::hasColumns('payments', [
            'booking_id', 'method', 'gateway_reference', 'gateway_url',
            'gateway_payload', 'amount', 'status', 'paid_at', 'expires_at',
        ]));

        $this->assertTrue(Schema::hasColumns('vehicles', [
            'vehicle_type_id', 'name', 'slug', 'brand', 'plate_number',
            'tank_capacity', 'price_per_day', 'image', 'description', 'is_active', 'deleted_at',
        ]));

        $this->assertTrue(Schema::hasColumns('vehicle_types', ['name', 'slug']));
    }

    /**
     * Happy path: booking + relasi + history + payment bisa dibuat.
     */
    public function test_booking_factory_and_relations_work(): void
    {
        $booking = Booking::factory()->create();

        $this->assertMatchesRegularExpression('/^EG\.\d{6}$/', $booking->code);
        $this->assertNotNull($booking->user);
        $this->assertNotNull($booking->vehicle);
        $this->assertInstanceOf(VehicleType::class, $booking->vehicle->type);

        $booking->histories()->create([
            'from_status' => null,
            'to_status' => 'pending',
            'note' => 'Booking dibuat',
        ]);

        $payment = Payment::factory()->create(['booking_id' => $booking->id, 'amount' => $booking->total_amount]);

        $this->assertSame(1, $booking->histories()->count());
        $this->assertSame(1, $booking->payments()->count());
        $this->assertTrue($booking->payments->first()->is($payment));
        $this->assertGreaterThan(0, VehicleType::count(), 'factory tipe motor tidak jalan');
        $this->assertGreaterThan(0, Vehicle::count(), 'factory motor tidak jalan');
    }

    /**
     * Kasus gagal: kode booking unik — duplikat harus ditolak database.
     */
    public function test_duplicate_booking_code_is_rejected(): void
    {
        $booking = Booking::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        Booking::factory()->create(['code' => $booking->code]);
    }

    /**
     * Seeder penuh (tipe, motor, booking, history, payment) jalan tanpa error.
     */
    public function test_database_seeder_runs(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(4, VehicleType::count());
        $this->assertGreaterThanOrEqual(10, Vehicle::count());
        $this->assertGreaterThanOrEqual(6, Booking::count());
        $this->assertGreaterThan(0, Payment::count());
        $this->assertTrue(
            Booking::query()->where('status', 'cancelled')->withCount('histories')->get()
                ->contains(fn ($booking) => $booking->histories_count > 0),
            'booking cancelled harus punya riwayat status'
        );
    }
}
