<?php

namespace App\Services;

use App\Models\EmailVerificationCode;
use App\Models\User;
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
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

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
     * Cocokkan kode. Mengembalikan null kalau benar, atau pesan error untuk ditampilkan.
     */
    public function verify(User $user, string $code): ?string
    {
        $record = EmailVerificationCode::where('user_id', $user->id)->first();

        if (! $record) {
            return 'Kode belum dikirim atau sudah tidak berlaku. Silakan kirim ulang kode.';
        }

        if ($record->expires_at->isPast()) {
            $record->delete();

            return 'Kode sudah kedaluwarsa. Silakan kirim ulang kode.';
        }

        if (! Hash::check($code, $record->code_hash)) {
            if ($record->attempts + 1 >= self::MAX_ATTEMPTS) {
                $record->delete();

                return 'Terlalu banyak percobaan salah. Silakan kirim ulang kode.';
            }

            $record->increment('attempts');

            return 'Kode salah. Periksa kembali kode di email Anda.';
        }

        $record->delete();

        return null;
    }
}
