<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Buffer antar sewa (menit) dari settings, default 60.
     */
    private function bufferMenit(): int
    {
        return (int) setting('booking.buffer_minutes', 60);
    }

    /**
     * Durasi maksimal sewa (hari) dari settings, default 5.
     */
    public function maxDays(): int
    {
        return (int) setting('booking.max_days', 5);
    }

    /**
     * Apakah unit tersedia pada [start, start + days×24 jam]?
     * Bentrok jika ada booking aktif dengan (start_at − buffer) < end AND (end_at + buffer) > start.
     */
    public function isAvailable(Vehicle $vehicle, CarbonInterface $start, int $days): bool
    {
        if (! $vehicle->is_active) {
            return false;
        }

        $end = $start->copy()->addDays($days);

        return ! $vehicle->bookings()
            ->active()
            ->overlapping($start, $end, $this->bufferMenit())
            ->exists();
    }

    /**
     * Query unit tersedia pada rentang (Builder, aman dipanggil ->paginate() tanpa N+1).
     */
    public function availableVehicles(CarbonInterface $start, int $days, ?int $typeId = null, ?string $search = null): Builder
    {
        $end = $start->copy()->addDays($days);
        $buffer = $this->bufferMenit();

        return Vehicle::query()
            ->where('is_active', true)
            ->with('type')
            ->when($typeId !== null, fn (Builder $query) => $query->where('vehicle_type_id', $typeId))
            ->when($search !== null && $search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ->whereDoesntHave('bookings', function (Builder $query) use ($start, $end, $buffer) {
                $query->active()->overlapping($start, $end, $buffer);
            });
    }

    /**
     * Opsi durasi1..maxDays beserta ketersediaannya, untuk modal pilih durasi.
     * Contoh: [1 => ['available' => true, 'end_at' => '2026-10-11T10:00:00+07:00'], ...]
     */
    public function availableDurations(Vehicle $vehicle, CarbonInterface $start, int $maxDays): array
    {
        $hasil = [];

        for ($days = 1; $days <= $maxDays; $days++) {
            $hasil[$days] = [
                'available' => $this->isAvailable($vehicle, $start, $days),
                'end_at' => $start->copy()->addDays($days)->toIso8601String(),
            ];
        }

        return $hasil;
    }

    /**
     * Jadwal terpakai per unit (rentang termasuk buffer) untuk detail/admin.
     */
    public function bookedRanges(Vehicle $vehicle, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $buffer = $this->bufferMenit();

        return $vehicle->bookings()
            ->active()
            ->overlapping($from, $to, $buffer)
            ->orderBy('start_at')
            ->get()
            ->map(fn (Booking $booking) => [
                'mulai' => $booking->start_at->copy()->subMinutes($buffer),
                'selesai' => $booking->end_at->copy()->addMinutes($buffer),
            ]);
    }
}
