<?php

namespace App\Services;

use App\Models\PendingRegistration;
use App\Models\User;
use App\Notifications\VerifyEmailCode;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Pendaftaran dua tahap: data disimpan sebagai pending, akun baru dibuat setelah kode OTP benar.
 */
class PendingRegistrationService
{
    /** Jeda minimal antar kirim ulang kode, sama dengan hitung mundur di halaman kode. */
    public const RESEND_COOLDOWN_SECONDS = 60;

    /** Kunci session berisi email pendaftaran yang sedang menunggu kode. */
    public const SESSION_KEY = 'pending_registration_email';

    public const ALREADY_REGISTERED = 'Email ini sudah terdaftar. Silakan masuk dengan akun Anda.';

    public function __construct(private EmailVerificationCodeService $codes) {}

    /**
     * Simpan (atau timpa) pendaftaran tertunda untuk email ini lalu kirim kode baru.
     *
     * @param  array{name: string, email: string, phone: string, password: string}  $data
     */
    public function start(array $data): PendingRegistration
    {
        (new PendingRegistration)->prunable()->delete();

        $pending = PendingRegistration::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'last_sent_at' => now(),
            ],
        );

        $this->sendCode($pending);

        return $pending;
    }

    /**
     * Sisa detik sebelum kode boleh dikirim ulang.
     */
    public function resendAvailableIn(PendingRegistration $pending): int
    {
        $elapsed = (int) $pending->last_sent_at->diffInSeconds(now(), true);

        return max(0, self::RESEND_COOLDOWN_SECONDS - $elapsed);
    }

    public function sendCode(PendingRegistration $pending): void
    {
        $code = $this->codes->issueForPending($pending);

        // Kirim setelah respons dikirim ke browser, tanpa butuh queue worker (sama seperti User).
        // Penanda $sent mencegah email dobel bila callback terminating ikut terpanggil di request berikutnya.
        $sent = false;
        app()->terminating(function () use ($pending, $code, &$sent) {
            if (! $sent) {
                $sent = true;
                $pending->notify(new VerifyEmailCode($code));
            }
        });
    }

    /**
     * Cocokkan kode lalu buat akun. Mengembalikan User kalau berhasil, atau pesan error.
     */
    public function complete(string $email, string $code): User|string
    {
        try {
            return DB::transaction(function () use ($email, $code) {
                $pending = PendingRegistration::where('email', $email)->lockForUpdate()->first();

                if (! $pending) {
                    return 'Pendaftaran tidak ditemukan atau sudah kedaluwarsa. Silakan daftar ulang.';
                }

                // Kode dicek dulu, supaya status email terdaftar tidak terbuka tanpa kode yang benar.
                if ($error = $this->codes->verifyPending($pending, $code)) {
                    return $error;
                }

                if (User::where('email', $email)->exists()) {
                    $pending->delete();

                    return self::ALREADY_REGISTERED;
                }

                $user = User::create([
                    'name' => $pending->name,
                    'email' => $pending->email,
                    'phone' => $pending->phone,
                    'password' => $pending->password,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
                $user->assignRole(RoleSeeder::USER);

                $pending->delete();

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            // Email yang sama baru saja menjadi user lewat request lain.
            PendingRegistration::where('email', $email)->delete();

            return self::ALREADY_REGISTERED;
        }
    }
}
