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
            ->whereNotNull('location_id')
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
            'profil' => $this->profil(),
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
     * Data section "Tentang Penyewaan" (EG-46); semua dari settings dengan fallback aman.
     */
    private function profil(): array
    {
        $waNumber = preg_replace('/\D+/', '', $this->textSetting('contact.whatsapp', ''));

        return [
            'logo' => $this->textSetting('brand.logo_light', ''),
            'nama' => $this->textSetting('brand.name', 'EduwGo'),
            'deskripsi' => $this->textSetting('brand.description', 'EduwGo menyediakan rental motor cepat, aman, dan terjangkau. Pilih motor, tentukan durasi, bayar online, lalu ambil di lokasi.'),
            'alamat' => $this->textSetting('contact.address', 'Alamat lokasi pengambilan akan diinformasikan oleh admin.'),
            'jam' => $this->textSetting('contact.hours', 'Setiap hari, 08.00 – 20.00 WIB'),
            'waUrl' => $waNumber !== '' ? 'https://wa.me/'.$waNumber : null,
            'sosmed' => collect([
                'Instagram' => setting('social.instagram'),
                'Facebook' => setting('social.facebook'),
                'TikTok' => setting('social.tiktok'),
                'YouTube' => setting('social.youtube'),
            ])->filter(fn ($href) => is_string($href) && trim($href) !== '' && $href !== '#'),
        ];
    }
}
