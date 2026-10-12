<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleFilterRequest;
use App\Models\Location;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\AvailabilityService;
use App\Support\RentalTerms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class VehicleController extends Controller
{
    private const RECOMMENDATION_LIMIT = 8;

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
        $lokasiSlug = $filter['lokasi'] ?? null;
        $lokasiId = $lokasiSlug !== null ? (int) Location::query()->where('slug', $lokasiSlug)->value('id') : null;
        $timeFiltered = $start !== null && $days !== null;

        // Normalisasi input harga & urut (whitelist / abaikan yang invalid)
        $sort = in_array($filter['harga'] ?? null, ['termurah', 'termahal'], true) ? $filter['harga'] : null;
        $min = (isset($filter['harga_min']) && is_numeric($filter['harga_min']) && $filter['harga_min'] >= 0) ? (int) $filter['harga_min'] : null;
        $max = (isset($filter['harga_max']) && is_numeric($filter['harga_max']) && $filter['harga_max'] >= 0) ? (int) $filter['harga_max'] : null;
        $popular = in_array($filter['urut'] ?? null, ['populer'], true);

        // Filter waktu & tipe dibawa ke detail motor & modal durasi lewat session (+ query string di link).
        // Null tidak disimpan; min/max disimpan sebagai string supaya lolos rule 'string' saat di-merge balik.
        $request->session()->put('booking.filter', array_filter([
            'lokasi' => $lokasiSlug,
            'start_at' => $start?->format('Y-m-d\TH:i'),
            'days' => $days,
            'type' => $typeId,
            'harga' => $sort,
            'harga_min' => $min !== null ? (string) $min : null,
            'harga_max' => $max !== null ? (string) $max : null,
            'urut' => $popular ? 'populer' : null,
        ], fn ($value) => filled($value)));

        if ($start !== null) {
            $request->session()->put('booking.start_at', $start->format('Y-m-d\TH:i'));
        }

        // Query katalog dibangun AvailabilityService (dipakai juga halaman lokasi, EG-49)
        $query = $availability->catalogQuery($start, $days, $typeId, $search, $onlyAvailable, $lokasiId);

        // Terapkan Query Tambahan, Jumlah Pesanan (withCount) dan Pengurutan
        $vehicles = $query
            ->withCount(['bookings as booking_count' => fn (Builder $q) => $q->whereIn('status', ['paid', 'rented', 'returned'])])
            ->when($min !== null, fn ($q) => $q->where('price_per_day', '>=', $min))
            ->when($max !== null, fn ($q) => $q->where('price_per_day', '<=', $max))
            ->when($popular, fn ($q) => $q->orderBy('booking_count', 'desc')->orderBy('name'))
            ->when(! $popular && $sort === 'termurah', fn ($q) => $q->orderBy('price_per_day')->orderBy('name'))
            ->when(! $popular && $sort === 'termahal', fn ($q) => $q->orderBy('price_per_day', 'desc')->orderBy('name'))
            ->when(! $popular && $sort === null, fn ($q) => $q->orderBy('name'))
            ->paginate(12)->withQueryString();

        return view('kendaraan.index', [
            'vehicles' => $vehicles,
            'types' => VehicleType::query()->orderBy('name')->pluck('name', 'id'),
            'lokasiOptions' => Location::query()->where('is_active', true)->orderBy('name')->pluck('name', 'slug'),
            'maxDays' => (int) $availability->maxDays(),
            'minStart' => VehicleFilterRequest::earliestStart(),
            'filter' => [
                'type' => $typeId,
                'lokasi' => $lokasiSlug,
                'start_at' => $start?->format('Y-m-d\TH:i'),
                'days' => $days,
                'available' => $onlyAvailable,
                'q' => $search,
                'harga' => $sort,
                'harga_min' => $min,
                'harga_max' => $max,
                'urut' => $popular ? 'populer' : null,
            ],
            'timeFiltered' => $timeFiltered,
            'terms' => RentalTerms::list(),
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
            'isAvailableNow' => $availability->isAvailable($vehicle, now(), 1),             'recommendations' => $recommendations,
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
