<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\AvailabilityService;

class HomeController extends Controller
{
    private const DEFAULT_TAGLINE = 'Rental Motor Cepat & Aman, Mulai Rp75.000/hari';

    private const DEFAULT_TERMS = [
        'Sewa minimal 1x24 jam.',
        'Konfirmasi pemesanan via WhatsApp atau datang langsung ke lokasi.',
        'Wajib membawa 2 identitas asli (KTP dan SIM C) saat pengambilan.',
        'Sertakan akun sosial media dan nomor WhatsApp yang aktif.',
        'Cek kondisi motor bersama petugas saat serah terima.',
        'Penyedia berhak membatalkan pesanan atau mengganti unit motor.',
    ];

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
            'terms' => $this->terms(),
            'vehicles' => $vehicles,
            'availableIds' => $availableIds->all(),
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
     * Poin S&K: array JSON atau teks per baris; jatuh ke default bila kosong.
     *
     * @return list<string>
     */
    private function terms(): array
    {
        $value = setting('content.terms');
        $items = is_string($value) ? (json_decode($value, true) ?? preg_split('/\R/', $value)) : $value;

        $items = is_array($items)
            ? array_values(array_filter(array_map(fn ($item) => is_string($item) ? trim($item) : '', $items)))
            : [];

        return $items ?: self::DEFAULT_TERMS;
    }
}
