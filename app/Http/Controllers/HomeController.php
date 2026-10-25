<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleFilterRequest;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use App\Services\BranchContext;
use App\Support\RentalTerms;

class HomeController extends Controller
{
    public function index(AvailabilityService $availability, BranchContext $branches)
    {
        $branchVehicles = Vehicle::query()
            ->where('is_active', true)
            ->where('branch_id', $branches->currentId());
        // Jumlah & harga termurah dalam satu query (DB produksi jauh: tiap query mahal)
        $summary = (clone $branchVehicles)->toBase()->selectRaw('COUNT(*) as total, MIN(price_per_day) as minimum')->first();
        $activeVehicleCount = (int) $summary->total;
        $vehicles = (clone $branchVehicles)
            ->with('type')
            ->withMin(['bookings as next_booking_start' => fn ($query) => $query->active()->where('end_at', '>', now())], 'start_at')
            ->orderBy('name')
            ->limit(8)
            ->get();
        $branches->attachTo($vehicles);

        // Satu query untuk badge status sekarang (hindari N+1 per kartu)
        $availableIds = $vehicles->isEmpty()
            ? collect()
            : $availability->availableVehicles(now(), 0)
                ->whereIn('id', $vehicles->pluck('id'))
                ->pluck('id');

        $minimum = $summary->minimum;
        $activeBranches = $branches->active();

        return view('home', [
            'minimumPrice' => $minimum,
            'terms' => RentalTerms::list(),
            'vehicles' => $vehicles,
            'activeVehicleCount' => $activeVehicleCount,
            'availableIds' => $availableIds->all(),
            'maxDays' => $availability->maxDays(),
            'minStart' => VehicleFilterRequest::earliestStart(),
            'profil' => $this->profil(),
            'branches' => $activeBranches,
            'branchAvailability' => $activeBranches->isEmpty()
                ? 'Informasi cabang segera tersedia'
                : 'Tersedia di '.$activeBranches->count().' cabang',
            'currentBranch' => $branches->current(),
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

    /**
     * Data section "Tentang Penyewaan" (EG-46): brand dari settings; alamat, jam, kontak & sosmed
     * dari tiap cabang aktif (lihat partial home.profil).
     */
    private function profil(): array
    {
        return [
            'nama' => $this->textSetting('brand.name', 'EduwGo'),
            'deskripsi' => preg_replace('/\s+cepat,\s*aman,\s*dan\s*terjangkau/iu', '', $this->textSetting('brand.description', 'EduwGo menyediakan sewa motor harian. Pilih jadwal dan ambil motor di cabang pilihan Anda.')),
        ];
    }
}
