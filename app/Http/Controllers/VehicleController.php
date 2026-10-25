<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleFilterRequest;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\AvailabilityService;
use App\Services\BranchContext;
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
    public function index(VehicleFilterRequest $request, AvailabilityService $availability, BranchContext $branches)
    {
        $filter = $request->validated();
        $start = $request->startAt();
        $days = isset($filter['days']) ? (int) $filter['days'] : null;
        $typeId = isset($filter['type']) ? (int) $filter['type'] : null;
        $search = $filter['q'] ?? null;
        $onlyAvailable = (bool) ($filter['available'] ?? false);
        $timeFiltered = $start !== null && $days !== null;

        // Normalisasi input harga & urut (whitelist / abaikan yang invalid)
        $sort = in_array($filter['harga'] ?? null, ['termurah', 'termahal'], true) ? $filter['harga'] : null;
        $min = (isset($filter['harga_min']) && is_numeric($filter['harga_min']) && $filter['harga_min'] >= 0) ? (int) $filter['harga_min'] : null;
        $max = (isset($filter['harga_max']) && is_numeric($filter['harga_max']) && $filter['harga_max'] >= 0) ? (int) $filter['harga_max'] : null;
        $popular = ($filter['harga'] ?? null) === 'populer' || in_array($filter['urut'] ?? null, ['populer'], true);

        // Filter waktu & tipe dibawa ke detail motor & modal durasi lewat session (+ query string di link).
        // Null tidak disimpan; min/max disimpan sebagai string supaya lolos rule 'string' saat di-merge balik.
        $request->session()->put('booking.filter', array_filter([
            'start_at' => $start?->format('Y-m-d\TH:i'),
            'days' => $days,
            'type' => $typeId,
            'harga' => $popular ? 'populer' : $sort,
            'harga_min' => $min !== null ? (string) $min : null,
            'harga_max' => $max !== null ? (string) $max : null,
            'urut' => $popular ? 'populer' : null,
        ], fn ($value) => filled($value)));

        if ($start !== null) {
            $request->session()->put('booking.start_at', $start->format('Y-m-d\TH:i'));
        }

        $query = $availability->catalogQuery($start, $days, $typeId, $search, $onlyAvailable, $branches->currentId());

        // Terapkan Query Tambahan, Jumlah Pesanan (withCount) dan Pengurutan
        $vehicles = $query
            ->withMin(['bookings as next_booking_start' => fn ($query) => $query->active()->where('end_at', '>', now())], 'start_at')
            ->withCount(['bookings as booking_count' => fn (Builder $q) => $q->whereIn('status', ['paid', 'rented', 'returned'])])
            ->when($min !== null, fn ($q) => $q->where('price_per_day', '>=', $min))
            ->when($max !== null, fn ($q) => $q->where('price_per_day', '<=', $max))
            ->when($popular, fn ($q) => $q->orderBy('booking_count', 'desc')->orderBy('name'))
            ->when(! $popular && $sort === 'termurah', fn ($q) => $q->orderBy('price_per_day')->orderBy('name'))
            ->when(! $popular && $sort === 'termahal', fn ($q) => $q->orderBy('price_per_day', 'desc')->orderBy('name'))
            ->when(! $popular && $sort === null, fn ($q) => $q->orderBy('name'))
            ->paginate(12)->withQueryString();
        $branches->attachTo($vehicles);

        return view('kendaraan.index', [
            'vehicles' => $vehicles,
            'types' => VehicleType::query()->orderBy('name')->pluck('name', 'id'),
            'branchOptions' => $branches->active()->pluck('name', 'slug'),
            'currentBranch' => $branches->current(),
            'maxDays' => (int) $availability->maxDays(),
            'minStart' => VehicleFilterRequest::earliestStart(),
            'filter' => [
                'type' => $typeId,
                'start_at' => $start?->format('Y-m-d\TH:i'),
                'days' => $days,
                'available' => $onlyAvailable,
                'q' => $search,
                'harga' => $popular ? 'populer' : $sort,
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

    public function show(Vehicle $vehicle, AvailabilityService $availability, BranchContext $branches)
    {
        abort_unless($vehicle->is_active && $branches->active()->contains('id', $vehicle->branch_id), 404);

        $branches->selectBranchOf($vehicle->branch_id);

        $vehicle->load('type');
        $branches->attachTo([$vehicle]);

        // Guest yang klik Booking diarahkan ke login lalu kembali ke halaman ini (jadwal di query ikut).
        if (auth()->guest()) {
            session()->put('url.intended', request()->fullUrl());
        }

        [$recommendations, $filtered] = $this->recommendations($vehicle, $availability);
        $branches->attachTo($recommendations);

        return view('kendaraan.show', [
            'vehicle' => $vehicle,
            'isAvailableNow' => $availability->isAvailable($vehicle, now(), 1),
            'recommendations' => $recommendations,
            'filtered' => $filtered,
            'backUrl' => $this->catalogBackUrl(),
            'terms' => array_slice(RentalTerms::list(), 0, 3),
        ]);
    }

    /**
     * Motor sejenis (tipe sama, cabang sama, aktif, bukan unit ini). Bila filter katalog ada di
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
            ? $availability->availableVehicles($start, $days, $vehicle->vehicle_type_id, null, $vehicle->branch_id)
            : $availability->catalogQuery(null, null, $vehicle->vehicle_type_id, null, false, $vehicle->branch_id)
                ->withMin(['bookings as next_booking_start' => fn ($query) => $query->active()->where('end_at', '>', now())], 'start_at');

        // Tipe & cabang sama dengan unit ini: pakai relasi yang sudah dimuat, tanpa eager load ulang
        $items = $query
            ->without('type')
            ->whereKeyNot($vehicle->getKey())
            ->latest('id')
            ->limit(self::RECOMMENDATION_LIMIT)
            ->get()
            ->each->setRelation('type', $vehicle->type);

        return [$items, $filtered];
    }

    /**
     * "Kembali ke hasil": URL katalog sebelumnya lengkap dengan query (cari, harga, dst.).
     * Selain itu katalog polos, yang memulihkan filter tersimpan di session.
     */
    private function catalogBackUrl(): string
    {
        $previous = url()->previous();

        return parse_url($previous, PHP_URL_PATH) === parse_url(route('kendaraan.index'), PHP_URL_PATH)
            ? $previous
            : route('kendaraan.index');
    }

    private function parseStart(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $start = Carbon::parse($value, config('app.timezone'))->setTimezone(config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }

        return $start->isPast() ? null : $start;
    }
}
