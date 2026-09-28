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
}
