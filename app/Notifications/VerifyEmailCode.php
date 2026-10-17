<?php

namespace App\Notifications;

use App\Services\EmailVerificationCodeService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailCode extends Notification
{
    public function __construct(public string $code) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode verifikasi email Anda')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Gunakan kode berikut untuk memverifikasi email Anda:')
            ->line('**'.$this->code.'**')
            ->line('Kode berlaku '.EmailVerificationCodeService::TTL_MINUTES.' menit. Jangan bagikan kode ini ke siapa pun.')
            ->line('Jika Anda tidak merasa mendaftar, abaikan email ini.');
    }
}
