<?php

namespace App\Console\Commands;

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
    protected $description = 'Auto-expire booking pending yang batas bayarnya (created_at + invoice_minutes) sudah lewat.';

    public function __construct(private PaymentService $paymentService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Kandidat dari batas bayar tetap booking (created_at + invoice_minutes),
        // bukan dari payment terakhir yang bisa terus diperbarui penyewa.
        $bookings = Booking::paymentDeadlinePassed()->get();

        $count = 0;

        foreach ($bookings as $booking) {
            // Status dicek ulang di dalam lock: webhook PAID yang menang duluan tidak ditimpa
            if ($this->paymentService->expireIfDeadlinePassed($booking)) {
                $count++;
            }
        }

        $this->info("Berhasil memproses $count booking expired.");
        Log::channel('xendit')->info('Scheduler bookings:expire selesai.', ['count' => $count]);

        return Command::SUCCESS;
    }
}
