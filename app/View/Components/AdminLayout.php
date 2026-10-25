<?php

namespace App\View\Components;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\View\Component;
use Illuminate\View\View;

class AdminLayout extends Component
{
    public function __construct(public ?string $title = null) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        $user = auth()->user();

        // Badge sidebar: pesanan yang perlu tindakan (menunggu bayar + terlambat) dan refund manual
        return view('layouts.admin', [
            'badges' => [
                'pesanan' => Booking::managedBy($user)
                    ->where(fn ($q) => $q->where('status', BookingStatus::Pending->value)->orWhere(fn ($q) => $q->overdue()))
                    ->count(),
                'refund' => Payment::awaitingRefund()->managedBy($user)->count(),
            ],
        ]);
    }
}
