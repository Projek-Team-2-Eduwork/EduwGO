<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\VehicleType;

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

        $inBranch = fn ($q) => $q->when(
            $branchId !== null,
            fn ($q) => $q->whereIn('vehicle_id', Vehicle::withTrashed()->where('branch_id', $branchId)->select('id')),
        );

        $bookingStats = Booking::query()->tap($inBranch)->selectRaw('
            SUM(CASE WHEN status IN ("pending", "paid", "rented") THEN 1 ELSE 0 END) as active_bookings,
            SUM(CASE WHEN status = "rented" THEN 1 ELSE 0 END) as rented_bookings
        ')->first();

        $activeBookings = (int) $bookingStats->active_bookings;
        $rentedBookings = (int) $bookingStats->rented_bookings;
        $availableVehicles = max(0, $activeVehicles - $rentedBookings);

        return [
            'total_vehicles' => $activeVehicles,
            'available_vehicles' => $availableVehicles,
            'active_bookings' => $activeBookings,
            'rented_bookings' => $rentedBookings,
            'overdue_vehicles' => Booking::overdue()->tap($inBranch)->count(),
        ];
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
            ->limit(8)
            ->get();
    }

    public function vehicleTypes()
    {
        return VehicleType::pluck('name', 'id');
    }
}
