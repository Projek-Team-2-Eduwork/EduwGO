<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class KendaraanController extends Controller
{
    /**
     * Opsi durasi sewa (1..maxDays) untuk modal pilih durasi.
     * GET /api/kendaraan/{vehicle}/durasi?start=YYYY-MM-DDTHH:mm (EG-11)
     */
    public function durasi(Request $request, Vehicle $vehicle, AvailabilityService $availability)
    {
        // Validasi start wajib dan tidak boleh di masa lalu
        $validated = $request->validate([
            'start' => ['required', 'date', 'after_or_equal:now'],
        ]);

        $start = Carbon::parse($validated['start']);

        return response()->json(
            $availability->availableDurations($vehicle, $start, $availability->maxDays())
        );
    }

    /**
     * Jadwal terpakai unit per hari dalam satu bulan, untuk kalender ketersediaan di detail motor.
     * GET /api/kendaraan/{vehicle}/jadwal?month=YYYY-MM (EG-41)
     *
     * Rentang sudah termasuk buffer (AvailabilityService::bookedRanges) dan dipotong per hari.
     * Hanya jam yang dikirim, tanpa data penyewa/booking (endpoint publik).
     */
    public function jadwal(Request $request, Vehicle $vehicle, AvailabilityService $availability)
    {
        abort_unless($vehicle->is_active, 404);

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $monthStart = Carbon::createFromFormat('Y-m-d H:i:s', ($validated['month'] ?? now()->format('Y-m')).'-01 00:00:00')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $days = [];

        foreach ($availability->bookedRanges($vehicle, $monthStart, $monthEnd) as $range) {
            // Potong rentang ke bulan ini lalu pecah per hari kalender.
            $cursor = $range['mulai']->copy()->max($monthStart)->startOfDay();
            $limit = $range['selesai']->copy()->min($monthEnd);

            while ($cursor->lte($limit)) {
                $dayStart = $cursor->copy()->startOfDay();
                $dayEnd = $cursor->copy()->endOfDay();

                $from = $range['mulai']->copy()->max($dayStart);
                $to = $range['selesai']->copy()->min($dayEnd);

                if ($from->lt($to)) {
                    $days[$cursor->toDateString()][] = $from->format('H:i').'–'.($to->gte($dayEnd->copy()->subMinute()) ? '24:00' : $to->format('H:i'));
                }

                $cursor->addDay();
            }
        }

        return response()->json([
            'month' => $monthStart->format('Y-m'),
            'buffer_minutes' => (int) setting('booking.buffer_minutes', 60),
            'days' => (object) $days,
        ]);
    }
}
