<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleFilterRequest;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\AvailabilityService;
use App\Support\RentalTerms;

class HomeController extends Controller
{
    private const DEFAULT_TAGLINE = 'Rental Motor Cepat & Aman, Mulai Rp75.000/hari';

    public function index(AvailabilityService $availability)
    {
        $vehicles = Vehicle::query()
            ->where('is_active', true)
            ->with('type')
            ->orderBy('name')
            ->limit(8)
            ->get();

        // Satu query untuk badge status sekarang (hindari N+1 per kartu)
        $availableIds = $vehicles->isEmpty()
            ? collect()
            : $availability->availableVehicles(now(), 0)
                ->whereIn('id', $vehicles->pluck('id'))
                ->pluck('id');

        return view('home', [
            'tagline' => $this->textSetting('brand.tagline', self::DEFAULT_TAGLINE),
            'terms' => RentalTerms::list(),
            'vehicles' => $vehicles,
            'availableIds' => $availableIds->all(),
            // Data kartu "Cari kendaraan" (mobile)
            'types' => VehicleType::query()->orderBy('name')->pluck('name', 'id'),
            'maxDays' => $availability->maxDays(),
            'minStart' => VehicleFilterRequest::earliestStart(),
        ]);
    }

    /**
     * Nilai setting teks; mendukung nilai JSON-string dari kolom settings.value.
     */
    private function textSetting(string $key, string $default): string
    {
        $value = setting($key, $default);
        $decoded = is_string($value) ? json_decode($value, true) : null;

        if (is_string($decoded)) {
            $value = $decoded;
        }

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }
}
