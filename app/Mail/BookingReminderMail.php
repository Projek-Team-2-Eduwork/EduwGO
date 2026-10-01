<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $type // 'pickup' atau 'return'
    ) {}

    public function envelope(): Envelope
    {
        $teks = $this->type === 'pickup' ? 'pengambilan besok' : 'pengembalian besok';

        return new Envelope(
            subject: 'Pengingat: '.$teks.' - Booking '.$this->booking->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-reminder',
        );
    }
}
