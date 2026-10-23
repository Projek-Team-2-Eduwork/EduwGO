<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use App\Services\BookingCodeGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;
use RuntimeException;

class BookingSeeder extends Seeder
{
    /**
     * Popularitas motor demo (jumlah booking dari total 30), agar donut
     * "5 Top Orderan" di dashboard admin punya urutan yang jelas.
     */
    private const WEIGHTS = [
        'honda-vario-160' => 5,
        'honda-scoopy' => 4,
        'honda-pcx-160' => 4,
        'honda-beat' => 4,
        'honda-adv-160' => 3,
        'honda-vario-125' => 3,
        'honda-genio' => 2,
        'honda-forza' => 2,
        'honda-stylo-160' => 2,
        'honda-supra-x-125' => 1,
    ];

    private Randomizer $rng;

    /**
     * 30 booking demo: 60 hari lalu s.d. 14 hari ke depan.
     * Komposisi: returned 18, rented 3, paid 3, pending 2, expired 3, cancelled 1.
     * Booking aktif (pending/paid/rented) dipasang hanya pada unit yang lolos
     * AvailabilityService (buffer antar sewa ikut dihitung). Riwayat status & payment
     * dibuat konsisten dengan state machine, lengkap dengan timestamp historis,
     * sehingga tidak lewat BookingService::transition() (yang selalu memakai waktu sekarang).
     */
    public function run(): void
    {
        $this->rng = new Randomizer(new Xoshiro256StarStar(hash('sha256', 'eg-38-booking-seeder', true)));

        $renters = User::role('user')->orderBy('id')->get();
        $admin = User::role('admin')->orderBy('id')->first();
        $vehicles = Vehicle::query()->where('is_active', true)->orderBy('id')->get()->keyBy('slug');

        if ($renters->isEmpty() || $vehicles->isEmpty()) {
            throw new RuntimeException('UserSeeder dan VehicleSeeder harus dijalankan sebelum BookingSeeder.');
        }

        $plans = collect($this->plans(CarbonImmutable::now()))
            ->sortBy(fn (array $plan) => $plan['created']->getTimestamp())
            ->values();

        $slugs = $this->preferredSlugs();

        DB::transaction(function () use ($plans, $slugs, $vehicles, $renters, $admin) {
            foreach ($plans as $index => $plan) {
                $vehicle = $this->pickVehicle($plan, $vehicles, $slugs[$index]);
                $this->createBooking($plan, $vehicle, $renters[$index % $renters->count()], $admin, $index);
            }
        });
    }

    /**
     * @return array<int, array{status: BookingStatus, start: CarbonImmutable, days: int, created: CarbonImmutable}>
     */
    private function plans(CarbonImmutable $now): array
    {
        $today = $now->startOfDay();
        $at = fn (int $offsetDays, int $hour, int $minute = 0) => $today->addDays($offsetDays)->setTime($hour, $minute);
        $plans = [];

        // returned: mulai 59..6 hari lalu, semuanya sudah selesai.
        for ($i = 0; $i < 18; $i++) {
            $offset = -59 + (int) round($i * 53 / 17) + $this->rng->getInt(-1, 1);
            $start = $at(min($offset, -6), $this->rng->getInt(7, 17), $this->rng->getInt(0, 1) * 30);
            $plans[] = [
                'status' => BookingStatus::Returned,
                'start' => $start,
                'days' => $this->rng->getInt(1, 4),
                'created' => $start->subDays($this->rng->getInt(1, 3))->subMinutes($this->rng->getInt(5, 600)),
            ];
        }

        // rented: 2 sedang berjalan, 1 terlambat kembali (contoh tanda overdue).
        foreach ([[-1, 9, 3], [-2, 10, 4], [-4, 8, 3]] as [$offset, $hour, $days]) {
            $start = $at($offset, $hour);
            $plans[] = ['status' => BookingStatus::Rented, 'start' => $start, 'days' => $days, 'created' => $start->subDay()];
        }

        // paid: sudah lunas, belum diambil.
        foreach ([[1, 10, 2, 2], [4, 9, 3, 1], [9, 13, 2, 3]] as [$offset, $hour, $days, $daysAgo]) {
            $plans[] = [
                'status' => BookingStatus::Paid,
                'start' => $at($offset, $hour),
                'days' => $days,
                'created' => $now->subDays($daysAgo)->subMinutes(30),
            ];
        }

        // pending: invoice masih berlaku (60 menit sejak dibuat).
        foreach ([[6, 11, 1, 12], [12, 8, 2, 25]] as [$offset, $hour, $days, $minutesAgo]) {
            $plans[] = [
                'status' => BookingStatus::Pending,
                'start' => $at($offset, $hour),
                'days' => $days,
                'created' => $now->subMinutes($minutesAgo),
            ];
        }

        // expired: invoice lewat batas 60 menit tanpa pembayaran.
        foreach ([[-28, 9, 2], [-9, 14, 3], [5, 10, 2]] as [$offset, $hour, $days]) {
            $start = $at($offset, $hour);
            $plans[] = [
                'status' => BookingStatus::Expired,
                'start' => $start,
                'days' => $days,
                'created' => $start->subDay()->min($now->subDays(2)),
            ];
        }

        // cancelled: dibatalkan penyewa saat masih pending.
        $plans[] = ['status' => BookingStatus::Cancelled, 'start' => $at(7, 10), 'days' => 3, 'created' => $now->subDays(3)];

        return $plans;
    }

    /**
     * Urutan slug motor pilihan per booking sesuai bobot popularitas (deterministik).
     *
     * @return array<int, string>
     */
    private function preferredSlugs(): array
    {
        $slugs = [];
        foreach (self::WEIGHTS as $slug => $count) {
            array_push($slugs, ...array_fill(0, $count, $slug));
        }

        return $this->rng->shuffleArray($slugs);
    }

    /**
     * Motor pilihan; booking aktif geser ke unit lain bila bentrok (termasuk buffer).
     *
     * @param  array{status: BookingStatus, start: CarbonImmutable, days: int, created: CarbonImmutable}  $plan
     * @param  Collection<string, Vehicle>  $vehicles
     */
    private function pickVehicle(array $plan, Collection $vehicles, string $preferred): Vehicle
    {
        if (! $plan['status']->isActive()) {
            return $vehicles[$preferred];
        }

        $availability = app(AvailabilityService::class);
        $candidates = $vehicles->sortBy(fn (Vehicle $vehicle) => $vehicle->slug === $preferred ? 0 : 1);

        foreach ($candidates as $vehicle) {
            if ($availability->isAvailable($vehicle, $plan['start'], $plan['days'])) {
                return $vehicle;
            }
        }

        throw new RuntimeException('Tidak ada unit tersedia untuk jadwal booking demo.');
    }

    /**
     * @param  array{status: BookingStatus, start: CarbonImmutable, days: int, created: CarbonImmutable}  $plan
     */
    private function createBooking(array $plan, Vehicle $vehicle, User $renter, ?User $admin, int $index): void
    {
        $status = $plan['status'];
        $created = $plan['created'];
        $end = $plan['start']->addDays($plan['days']);
        $expiresAt = $created->addMinutes(60);
        $total = $vehicle->price_per_day * $plan['days'];

        $booking = Booking::query()->create([
            'code' => app(BookingCodeGenerator::class)->next(),
            'user_id' => $renter->id,
            'vehicle_id' => $vehicle->id,
            'start_at' => $plan['start'],
            'end_at' => $end,
            'duration_days' => $plan['days'],
            'price_per_day' => $vehicle->price_per_day,
            'total_amount' => $total,
            'status' => $status,
            'customer_name' => $renter->name,
            'customer_phone' => $renter->phone,
            'notes' => $index % 4 === 0 ? 'Mohon helm disiapkan 2 buah.' : null,
            'cancel_reason' => $status === BookingStatus::Cancelled ? 'Berubah rencana, perjalanan ditunda.' : null,
            'created_at' => $created,
            'updated_at' => $created,
        ]);

        // Jejak transisi sesuai state machine (pending → paid → rented → returned | expired | cancelled).
        $trail = [[null, BookingStatus::Pending, $renter->id, 'Booking dibuat via checkout', $created]];
        $paidAt = $created->addMinutes($this->rng->getInt(3, 40));

        match ($status) {
            BookingStatus::Pending => null,
            BookingStatus::Expired => $trail[] = [BookingStatus::Pending, $status, null, 'Invoice kedaluwarsa', $expiresAt],
            BookingStatus::Cancelled => $trail[] = [BookingStatus::Pending, $status, $renter->id, $booking->cancel_reason, $created->addHours(2)],
            default => $trail[] = [BookingStatus::Pending, BookingStatus::Paid, null, 'Pembayaran via Xendit', $paidAt],
        };

        if (in_array($status, [BookingStatus::Rented, BookingStatus::Returned], true)) {
            $trail[] = [BookingStatus::Paid, BookingStatus::Rented, $admin?->id, 'Serah terima unit', $plan['start']];
        }

        if ($status === BookingStatus::Returned) {
            $trail[] = [BookingStatus::Rented, BookingStatus::Returned, $admin?->id, 'Unit diterima kembali', $end];
        }

        foreach ($trail as [$from, $to, $by, $note, $at]) {
            $booking->histories()->create([
                'from_status' => $from?->value,
                'to_status' => $to->value,
                'changed_by' => $by,
                'note' => $note,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        }

        $paymentStatus = match ($status) {
            BookingStatus::Pending => 'pending',
            BookingStatus::Expired => 'expired',
            BookingStatus::Cancelled => 'failed',
            default => 'paid',
        };
        $reference = 'inv_'.str_pad((string) $booking->id, 10, '0', STR_PAD_LEFT);

        $booking->payments()->create([
            'method' => 'xendit_invoice',
            'gateway_reference' => $reference,
            'gateway_url' => 'https://checkout.xendit.co/web/'.$reference,
            'gateway_payload' => [
                'id' => $reference,
                'external_id' => $booking->code,
                'status' => strtoupper($paymentStatus === 'failed' ? 'expired' : $paymentStatus),
                'seeded' => true,
            ],
            'amount' => $total,
            'status' => $paymentStatus,
            'paid_at' => $paymentStatus === 'paid' ? $paidAt : null,
            'expires_at' => $expiresAt,
            'created_at' => $created,
            'updated_at' => $paymentStatus === 'paid' ? $paidAt : $created,
        ]);
    }
}
