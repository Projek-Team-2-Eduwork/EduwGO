<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleFilterRequest;
use App\Models\Location;
use App\Models\VehicleType;
use App\Services\AvailabilityService;
use App\Support\RentalTerms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LokasiPublikController extends Controller
{
    /**
     * Daftar lokasi aktif + jumlah unit aktif tiap lokasi (query tunggal, aman N+1). (EG-49)
     */
    public function index()
    {
        $lokasi = Location::query()
            ->where('is_active', true)
            ->withCount(['vehicles as units_count' => fn (Builder $q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return view('lokasi.index', ['lokasi' => $lokasi]);
    }

    /**
     * Unit satu lokasi: reuse logika filter katalog lewat AvailabilityService. (EG-49)
     */
    public function show(string $slug, VehicleFilterRequest $request, AvailabilityService $availability)
    {
        $lokasi = Location::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $filter = $request->validated();
        $start = $request->startAt();
        $days = isset($filter['days']) ? (int) $filter['days'] : null;
        $typeId = isset($filter['type']) ? (int) $filter['type'] : null;
        $search = $filter['q'] ?? null;
        $onlyAvailable = (bool) ($filter['available'] ?? false);

        // Normalisasi input harga & urut (sama dengan katalog)
        $sort = in_array($filter['harga'] ?? null, ['termurah', 'termahal'], true) ? $filter['harga'] : null;
        $min = (isset($filter['harga_min']) && is_numeric($filter['harga_min']) && $filter['harga_min'] >= 0) ? (int) $filter['harga_min'] : null;
        $max = (isset($filter['harga_max']) && is_numeric($filter['harga_max']) && $filter['harga_max'] >= 0) ? (int) $filter['harga_max'] : null;
        $popular = in_array($filter['urut'] ?? null, ['populer'], true);

        // Filter dibawa ke detail motor & modal durasi lewat session (sama dengan katalog)
        $request->session()->put('booking.filter', array_filter([
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

        $timeFiltered = $start !== null && $days !== null;

        $vehicles = $availability->catalogQuery($start, $days, $typeId, $search, $onlyAvailable, $lokasi->id)
            ->withCount(['bookings as booking_count' => fn (Builder $q) => $q->whereIn('status', ['paid', 'rented', 'returned'])])
            ->when($min !== null, fn ($q) => $q->where('price_per_day', '>=', $min))
            ->when($max !== null, fn ($q) => $q->where('price_per_day', '<=', $max))
            ->when($popular, fn ($q) => $q->orderBy('booking_count', 'desc')->orderBy('name'))
            ->when(! $popular && $sort === 'termurah', fn ($q) => $q->orderBy('price_per_day')->orderBy('name'))
            ->when(! $popular && $sort === 'termahal', fn ($q) => $q->orderBy('price_per_day', 'desc')->orderBy('name'))
            ->when(! $popular && $sort === null, fn ($q) => $q->orderBy('name'))
            ->paginate(12)->withQueryString();

        return view('lokasi.show', [
            'lokasi' => $lokasi,
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
     * Hapus filter session lalu kembali ke halaman lokasi polos.
     */
    public function resetFilter(Request $request, string $slug)
    {
        $request->session()->forget(['booking.filter', 'booking.start_at']);

        return redirect()->route('lokasi.show', $slug);
    }
}
