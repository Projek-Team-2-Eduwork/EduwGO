<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Services\PendingRegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Halaman kode OTP untuk pendaftar yang belum punya akun (tanpa login).
 */
class PendingRegistrationController extends Controller
{
    public function __construct(private PendingRegistrationService $registrations) {}

    public function show(Request $request): View|RedirectResponse
    {
        $pending = $this->pending($request);

        if (! $pending) {
            return $this->restart($request);
        }

        return view('auth.verify-email', [
            'email' => $pending->email,
            'verifyAction' => route('register.verify.store'),
            'resendAction' => route('register.resend'),
            'resendIn' => $this->registrations->resendAvailableIn($pending),
            'pendingRegistration' => true,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $email = $request->session()->get(PendingRegistrationService::SESSION_KEY);

        if (! $email) {
            return $this->restart($request);
        }

        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Kode verifikasi wajib diisi.',
            'code.digits' => 'Kode verifikasi terdiri dari 6 angka.',
        ]);

        $result = $this->registrations->complete($email, $data['code']);

        if ($result === PendingRegistrationService::ALREADY_REGISTERED) {
            $request->session()->forget(PendingRegistrationService::SESSION_KEY);

            return redirect()->route('login')
                ->withInput(['email' => $email])
                ->withErrors(['email' => $result]);
        }

        if (! $result instanceof User) {
            throw ValidationException::withMessages(['code' => $result]);
        }

        $request->session()->forget(PendingRegistrationService::SESSION_KEY);

        Auth::login($result);
        $request->session()->regenerate();

        event(new Registered($result));
        event(new Verified($result));

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }

    public function resend(Request $request): RedirectResponse
    {
        $pending = $this->pending($request);

        if (! $pending) {
            return $this->restart($request);
        }

        if ($wait = $this->registrations->resendAvailableIn($pending)) {
            return back()->withErrors(['code' => "Tunggu {$wait} detik sebelum mengirim ulang kode."]);
        }

        $this->registrations->sendCode($pending);

        return back()->with('status', 'verification-code-sent');
    }

    /**
     * Batalkan pendaftaran tertunda dan kembali ke form daftar (untuk ubah email atau mulai ulang).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $pending = $this->pending($request);

        $request->session()->forget(PendingRegistrationService::SESSION_KEY);

        if (! $pending) {
            return redirect()->route('register');
        }

        $pending->delete();

        return redirect()->route('register')->withInput($pending->only('name', 'email', 'phone'));
    }

    private function pending(Request $request): ?PendingRegistration
    {
        $email = $request->session()->get(PendingRegistrationService::SESSION_KEY);

        return $email
            ? PendingRegistration::where('email', $email)
                ->where('last_sent_at', '>=', now()->subHours(PendingRegistration::TTL_HOURS))
                ->first()
            : null;
    }

    private function restart(Request $request): RedirectResponse
    {
        $request->session()->forget(PendingRegistrationService::SESSION_KEY);

        return redirect()->route('register')
            ->withErrors(['email' => 'Sesi pendaftaran tidak ditemukan atau sudah kedaluwarsa. Silakan daftar ulang.']);
    }
}
