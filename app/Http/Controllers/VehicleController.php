<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleFilterRequest;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\AvailabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * Katalog kendaraan + filter tipe, waktu mulai, durasi, ketersediaan, dan pencarian nama (EG-24).
     */
    public function index(VehicleFilterRequest $request, AvailabilityService $availability)
    {
        $filter = $request->validated();
        $start = $request->startAt();
        $days = isset($filter['days']) ? (int) $filter['days'] : null;
        $typeId = isset($filter['type']) ? (int) $filter['type'] : null;
        $search = $filter['q'] ?? null;
        $onlyAvailable = (bool) ($filter['available'] ?? false);
        $timeFiltered = $start !== null && $days !== null;

        // Filter waktu & tipe dibawa ke detail motor & modal durasi lewat session (+ query string di link).
        $request->session()->put('booking.filter', [
            'start_at' => $start?->format('Y-m-d\TH:i'),
            'days' => $days,
            'type' => $typeId,
        ]);

        if ($start !== null) {
            $request->session()->put('booking.start_at', $start->format('Y-m-d\TH:i'));
        }

        if ($timeFiltered) {
            // Hanya unit yang bebas di rentang [mulai, mulai + durasi] (termasuk buffer).
            $query = $availability->availableVehicles($start, $days, $typeId, $search);
        } else {
            $query = Vehicle::query()
                ->where('is_active', true)
                ->with('type')
                ->when($typeId !== null, fn (Builder $q) => $q->where('vehicle_type_id', $typeId))
                ->when(filled($search), fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));

            // Status saat ini: sedang ada booking aktif yang berjalan.
            $running = fn (Builder $q) => $q->active()->where('start_at', '<=', now())->where('end_at', '>', now());

            $onlyAvailable
                ? $query->whereDoesntHave('bookings', $running)
                : $query->withExists(['bookings as is_rented' => $running]);
        }

        $vehicles = $query->orderBy('name')->paginate(12)->withQueryString();

        return view('kendaraan.index', [
            'vehicles' => $vehicles,
            'types' => VehicleType::query()->orderBy('name')->pluck('name', 'id'),
            'maxDays' => (int) $availability->maxDays(),
            'minStart' => VehicleFilterRequest::earliestStart(),
            'filter' => [
                'type' => $typeId,
                'start_at' => $start?->format('Y-m-d\TH:i'),
                'days' => $days,
                'available' => $onlyAvailable,
                'q' => $search,
            ],
            'timeFiltered' => $timeFiltered,
        ]);
    }

    /**
     * Hapus filter tersimpan di session lalu kembali ke katalog polos.
     */
    public function resetFilter(Request $request)
    {
        $request->session()->forget(['booking.filter', 'booking.start_at']);

        return redirect()->route('kendaraan.index');
    }

    public function show(Vehicle $vehicle)
    {
        return view('kendaraan.show', compact('vehicle'));
    }
}
