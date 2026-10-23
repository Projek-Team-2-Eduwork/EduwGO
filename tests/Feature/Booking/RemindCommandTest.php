<?php

namespace Tests\Feature\Booking;

use App\Mail\BookingReminderMail;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RemindCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_pengingat_terkirim_untuk_jadwal_besok_sesuai_status()
    {
        Mail::fake();
        Carbon::setTestNow('2026-10-10 08:00:00');
        $besok = Carbon::parse('2026-10-11');

        // Harus terkirim (Pickup)
        $pickup = Booking::factory()->paid()->create([
            'start_at' => $besok->copy()->setHour(10),
            'end_at' => $besok->copy()->addDays(2),
        ]);

        // Harus terkirim (Return)
        $return = Booking::factory()->rented()->create([
            'start_at' => $besok->copy()->subDays(2),
            'end_at' => $besok->copy()->setHour(12),
        ]);

        // Abaikan (Tanggal bukan besok)
        Booking::factory()->paid()->create([
            'start_at' => Carbon::parse('2026-10-12')->setHour(10),
        ]);
        Booking::factory()->rented()->create([
            'end_at' => Carbon::parse('2026-10-09')->setHour(10),
        ]);

        // Abaikan (Status tidak sesuai)
        Booking::factory()->pending()->create([
            'start_at' => $besok->copy()->setHour(10),
        ]);

        $this->artisan('bookings:remind')->assertSuccessful();

        Mail::assertSent(BookingReminderMail::class, function (BookingReminderMail $mail) use ($pickup) {
            return $mail->hasTo($pickup->user->email) && $mail->type === 'pickup';
        });

        Mail::assertSent(BookingReminderMail::class, function (BookingReminderMail $mail) use ($return) {
            return $mail->hasTo($return->user->email) && $mail->type === 'return';
        });

        Mail::assertSentTimes(BookingReminderMail::class, 2);
    }
}
