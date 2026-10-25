<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardService
{
    public function topVehicles(?int $branchId = null)
    {
        return Vehicle::query()
            ->selectRaw('vehicles.id, vehicles.name, COUNT(bookings.id) as booking_count')
            ->join('bookings', 'vehicles.id', '=', 'bookings.vehicle_id')
            ->when($branchId !== null, fn ($q) => $q->where('vehicles.branch_id', $branchId))
            ->whereIn('bookings.status', ['paid', 'rented', 'returned'])
            ->groupBy('vehicles.id', 'vehicles.name')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get();
    }

    public function stats(?int $branchId = null)
    {
        $activeVehicles = Vehicle::where('is_active', true)
            ->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        $inBranch = $this->inBranch($branchId);

        $bookingStats = Booking::query()->tap($inBranch)->selectRaw('
            SUM(CASE WHEN status IN ("pending", "paid", "rented") THEN 1 ELSE 0 END) as active_bookings,
            SUM(CASE WHEN status = "rented" THEN 1 ELSE 0 END) as rented_bookings,
            SUM(CASE WHEN status = "rented" AND end_at < ? THEN 1 ELSE 0 END) as overdue_bookings
        ', [now()])->first();

        $activeBookings = (int) $bookingStats->active_bookings;
        $rentedBookings = (int) $bookingStats->rented_bookings;
        $availableVehicles = max(0, $activeVehicles - $rentedBookings);

        return [
            'total_vehicles' => $activeVehicles,
            'available_vehicles' => $availableVehicles,
            'active_bookings' => $activeBookings,
            'rented_bookings' => $rentedBookings,
            'overdue_vehicles' => (int) $bookingStats->overdue_bookings,
        ];
    }

    /**
     * Pendapatan per cabang ($branches: [id => nama]); filter periode/tipe/metode sama dengan total, cabang tanpa transaksi = 0.
     */
    public function revenueByBranch(Collection $branches, $from, $to, $typeId = null, $method = null): Collection
    {
        $totals = Payment::query()
            ->join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->join('vehicles', 'bookings.vehicle_id', '=', 'vehicles.id')
            ->where('payments.status', 'paid')
            ->where('bookings.status', '!=', 'cancelled')
            ->whereBetween('payments.paid_at', [$from, $to])
            ->when($typeId, fn ($q) => $q->where('vehicles.vehicle_type_id', $typeId))
            ->when($method, fn ($q) => $q->where('payments.method', $method))
            ->groupBy('vehicles.branch_id')
            ->selectRaw('vehicles.branch_id, SUM(payments.amount) as total')
            ->pluck('total', 'vehicles.branch_id');

        return $branches
            ->map(fn (string $name, int $id) => ['id' => $id, 'name' => $name, 'total' => (float) ($totals[$id] ?? 0)])
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Jadwal serah terima hari ini: unit diambil (Lunas, mulai hari ini) dan kembali (Disewa, selesai hari ini).
     */
    public function todaySchedule(?int $branchId = null): Collection
    {
        $day = [now()->startOfDay(), now()->endOfDay()];

        return Booking::query()->tap($this->inBranch($branchId))->with('vehicle')
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('status', BookingStatus::Paid->value)->whereBetween('start_at', $day))
                ->orWhere(fn ($q) => $q->where('status', BookingStatus::Rented->value)->whereBetween('end_at', $day)))
            ->get()
            ->map(fn (Booking $b) => $b->status === BookingStatus::Paid
                ? ['type' => 'ambil', 'at' => $b->start_at, 'booking' => $b]
                : ['type' => 'kembali', 'at' => $b->end_at, 'booking' => $b])
            ->sortBy('at')
            ->values();
    }

    public function overdueBookings(?int $branchId = null): Collection
    {
        return Booking::overdue()->tap($this->inBranch($branchId))->with('vehicle')->orderBy('end_at')->limit(10)->get();
    }

    public function refundsAwaiting(?int $branchId = null): Collection
    {
        return Payment::awaitingRefund()
            ->whereIn('booking_id', Booking::query()->tap($this->inBranch($branchId))->select('id'))
            ->with('booking')
            ->latest('paid_at')
            ->get();
    }

    private function inBranch(?int $branchId): \Closure
    {
        return fn (Builder $q) => $q->when(
            $branchId !== null,
            fn ($q) => $q->whereIn('vehicle_id', Vehicle::withTrashed()->where('branch_id', $branchId)->select('id')),
        );
    }

    public function revenue($from, $to, $typeId = null, $method = null, ?int $branchId = null)
    {
        $query = Payment::query()
            ->join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->where('payments.status', 'paid')
            ->where('bookings.status', '!=', 'cancelled')
            ->whereBetween('payments.paid_at', [$from, $to]);

        if ($typeId || $branchId !== null) {
            $query->join('vehicles', 'bookings.vehicle_id', '=', 'vehicles.id')
                ->when($typeId, fn ($q) => $q->where('vehicles.vehicle_type_id', $typeId))
                ->when($branchId !== null, fn ($q) => $q->where('vehicles.branch_id', $branchId));
        }

        if ($method) {
            $query->where('payments.method', $method);
        }

        return (float) $query->sum('payments.amount');
    }

    public function recentBookings(?int $branchId = null)
    {
        return Booking::query()
            ->select('bookings.*', 'vehicles.name as vehicle_name', 'vehicles.image as vehicle_image', 'vehicle_types.name as vehicle_type_name')
            ->join('vehicles', 'bookings.vehicle_id', '=', 'vehicles.id')
            ->join('vehicle_types', 'vehicles.vehicle_type_id', '=', 'vehicle_types.id')
            ->when($branchId !== null, fn ($q) => $q->where('vehicles.branch_id', $branchId))
            ->orderByDesc('bookings.created_at')
            ->limit(5)
            ->get();
    }

    public function vehicleTypes()
    {
        return VehicleType::pluck('name', 'id');
    }
}
