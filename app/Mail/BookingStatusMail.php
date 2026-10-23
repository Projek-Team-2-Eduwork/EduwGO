<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $subjectText
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectText.' - Booking '.$this->booking->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-status',
        );
    }
}
