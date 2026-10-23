<?php

namespace App\Services;

use App\Models\EmailVerificationCode;
use App\Models\PendingRegistration;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EmailVerificationCodeService
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /**
     * Buat kode 6 angka baru (kode lama hangus). Yang disimpan hanya hash-nya.
     */
    public function issue(User $user): string
    {
        $code = $this->generate();

        EmailVerificationCode::updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
                'attempts' => 0,
            ],
        );

        return $code;
    }

    /**
     * Sama seperti issue(), tetapi untuk pendaftaran yang belum menjadi user.
     */
    public function issueForPending(PendingRegistration $pending): string
    {
        $code = $this->generate();

        $pending->forceFill([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'attempts' => 0,
            'last_sent_at' => now(),
        ])->save();

        return $code;
    }

    /**
     * Cocokkan kode. Mengembalikan null kalau benar, atau pesan error untuk ditampilkan.
     */
    public function verify(User $user, string $code): ?string
    {
        $record = EmailVerificationCode::where('user_id', $user->id)->first();

        return $this->check($record, $code, fn () => $record->delete());
    }

    /**
     * Cocokkan kode pendaftaran tertunda. Data pendaftaran tetap disimpan supaya kode bisa dikirim ulang.
     */
    public function verifyPending(PendingRegistration $pending, string $code): ?string
    {
        return $this->check(
            $pending->code_hash ? $pending : null,
            $code,
            fn () => $pending->forceFill(['code_hash' => null, 'expires_at' => null, 'attempts' => 0])->save(),
        );
    }

    private function generate(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param  Closure(): mixed  $discard  menghanguskan kode (dipanggil saat kedaluwarsa, salah terlalu sering, atau berhasil)
     */
    private function check(?Model $record, string $code, Closure $discard): ?string
    {
        if (! $record) {
            return 'Kode belum dikirim atau sudah tidak berlaku. Silakan kirim ulang kode.';
        }

        if ($record->expires_at->isPast()) {
            $discard();

            return 'Kode sudah kedaluwarsa. Silakan kirim ulang kode.';
        }

        // Hash::check memakai password_verify yang aman terhadap timing attack.
        if (! Hash::check($code, $record->code_hash)) {
            if ($record->attempts + 1 >= self::MAX_ATTEMPTS) {
                $discard();

                return 'Terlalu banyak percobaan salah. Silakan kirim ulang kode.';
            }

            $record->increment('attempts');

            return 'Kode salah. Periksa kembali kode di email Anda.';
        }

        $discard();

        return null;
    }
}
