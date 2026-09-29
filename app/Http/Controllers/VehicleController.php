<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\AvailabilityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class VehicleController extends Controller
{
    private const RECOMMENDATION_LIMIT = 8;

    public function show(Vehicle $vehicle, AvailabilityService $availability)
    {
        // Motor nonaktif dianggap tidak ada; soft-deleted sudah 404 lewat route binding.
        abort_unless($vehicle->is_active, 404);

        $vehicle->load('type');

        // Guest yang klik Booking diarahkan ke login lalu kembali ke halaman ini.
        if (auth()->guest()) {
            session()->put('url.intended', route('kendaraan.detail', $vehicle));
        }

        [$recommendations, $filtered] = $this->recommendations($vehicle, $availability);

        return view('kendaraan.show', [
            'vehicle' => $vehicle,
            'isAvailableNow' => $availability->isAvailable($vehicle, now(), 1),
            'recommendations' => $recommendations,
            'filtered' => $filtered,
        ]);
    }

    /**
     * Motor sejenis (tipe sama, aktif, bukan unit ini). Bila filter katalog ada di
     * session `booking.filter`, hanya yang tersedia di rentang itu.
     *
     * @return array{0: Collection<int, Vehicle>, 1: bool}
     */
    private function recommendations(Vehicle $vehicle, AvailabilityService $availability): array
    {
        $filter = (array) session('booking.filter', []);
        $start = $this->parseStart($filter['start_at'] ?? null);
        $days = (int) ($filter['days'] ?? 0);
        $filtered = $start !== null && $days >= 1 && $days <= $availability->maxDays();

        $query = $filtered
            ? $availability->availableVehicles($start, $days, $vehicle->vehicle_type_id)
            : Vehicle::query()->where('is_active', true)->where('vehicle_type_id', $vehicle->vehicle_type_id)->with('type');

        $items = $query
            ->whereKeyNot($vehicle->getKey())
            ->latest('id')
            ->limit(self::RECOMMENDATION_LIMIT)
            ->get();

        return [$items, $filtered];
    }

    private function parseStart(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $start = Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }

        return $start->isPast() ? null : $start;
    }
}
