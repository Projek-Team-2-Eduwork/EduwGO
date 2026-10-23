<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-expire booking pending yang invoice-nya sudah lewat waktu (expired).';

    public function __construct(private PaymentService $paymentService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $bookings = Booking::where('status', BookingStatus::Pending)
            ->with('latestPayment')
            ->get()
            ->filter(function ($booking) {
                return $booking->latestPayment !== null
                    && $booking->latestPayment->expires_at
                    && $booking->latestPayment->expires_at->isPast();
            });

        $count = 0;

        foreach ($bookings as $booking) {
            $this->paymentService->applyInvoiceStatus($booking->latestPayment, ['status' => 'EXPIRED']);
            $count++;
        }

        $this->info("Berhasil memproses $count booking expired.");
        Log::channel('xendit')->info('Scheduler bookings:expire selesai.', ['count' => $count]);

        return Command::SUCCESS;
    }
}
