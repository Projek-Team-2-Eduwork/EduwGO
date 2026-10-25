<?php

namespace App\Services;

use App\Models\PendingRegistration;
use App\Models\User;
use App\Notifications\VerifyEmailCode;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Pendaftaran dua tahap: data disimpan sebagai pending, akun baru dibuat setelah kode OTP benar.
 */
class PendingRegistrationService
{
    /** Jeda minimal antar kirim ulang kode, sama dengan hitung mundur di halaman kode. */
    public const RESEND_COOLDOWN_SECONDS = 60;

    /** Kunci session berisi email pendaftaran yang sedang menunggu kode. */
    public const SESSION_KEY = 'pending_registration_email';

    public const TOKEN_KEY = 'pending_registration_token';

    public const ALREADY_REGISTERED = 'Email ini sudah terdaftar. Silakan masuk dengan akun Anda.';

    public function __construct(private EmailVerificationCodeService $codes) {}

    /**
     * Simpan pendaftaran milik sesi ini lalu kirim kode sesuai cooldown per email.
     *
     * @param  array{name: string, email: string, phone: string, password: string}  $data
     */
    public function start(array $data, string $sessionToken): PendingRegistration
    {
        (new PendingRegistration)->prunable()->delete();

        try {
            return DB::transaction(function () use ($data, $sessionToken) {
                $pending = PendingRegistration::where('email', $data['email'])->lockForUpdate()->first();

                if ($pending && (! $this->owns($pending, $sessionToken) || $this->resendAvailableIn($pending))) {
                    throw ValidationException::withMessages(['email' => 'Pendaftaran masih menunggu verifikasi. Gunakan sesi awal atau tunggu hingga kedaluwarsa.']);
                }

                $pending ??= new PendingRegistration(['email' => $data['email']]);
                $pending->fill([
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make($data['password']),
                ]);
                $pending->forceFill(['session_token_hash' => hash('sha256', $sessionToken)]);
                $this->sendCode($pending);

                return $pending;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'Pendaftaran sedang diproses. Silakan coba lagi.']);
        }
    }

    public function owns(PendingRegistration $pending, ?string $sessionToken): bool
    {
        return $sessionToken && $pending->session_token_hash
            && hash_equals($pending->session_token_hash, hash('sha256', $sessionToken));
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
        $key = 'registration-code:'.hash('sha256', strtolower($pending->email));
        if (RateLimiter::tooManyAttempts($key.':cooldown', 1)) {
            throw ValidationException::withMessages(['email' => 'Tunggu 60 detik sebelum mengirim ulang kode.']);
        }
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages(['email' => 'Terlalu banyak pengiriman kode. Silakan coba lagi dalam satu jam.']);
        }
        RateLimiter::hit($key, 3600);
        RateLimiter::hit($key.':cooldown', self::RESEND_COOLDOWN_SECONDS);
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
    public function complete(string $email, string $code, ?string $sessionToken): User|string
    {
        try {
            return DB::transaction(function () use ($email, $code, $sessionToken) {
                $pending = PendingRegistration::where('email', $email)->lockForUpdate()->first();

                if (! $pending || ! $this->owns($pending, $sessionToken)) {
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
