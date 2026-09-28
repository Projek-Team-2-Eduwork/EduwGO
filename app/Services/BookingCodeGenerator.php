<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Sequence;

class BookingCodeGenerator
{
    /**
     * Menghasilkan kode booking unik berurutan (contoh: EG.000001).
     */
    public function next(): string
    {
        $sequence = Sequence::where('key', 'booking')->lockForUpdate()->first();
        $next = $sequence->value + 1;

        do {
            $code = 'EG.'.str_pad($next, 6, '0', STR_PAD_LEFT);
            if (! Booking::where('code', $code)->exists()) {
                break;
            }
            $next++;
        } while (true);

        $sequence->update(['value' => $next]);

        return $code;
    }
}
