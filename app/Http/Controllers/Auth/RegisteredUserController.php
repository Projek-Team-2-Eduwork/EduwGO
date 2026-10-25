<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\PendingRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    private const DEFAULT_TERMS = "1. Sewa minimal 1×24 jam.\n2. Konfirmasi pemesanan via WhatsApp atau datang langsung.\n3. Bawa 2 identitas asli saat pengambilan motor.\n4. Sertakan akun sosial media dan nomor WhatsApp yang aktif.\n5. Cek kondisi motor bersama saat serah terima.\n6. Penyedia berhak membatalkan pesanan atau mengganti unit.";

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', ['terms' => $this->terms()]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request, PendingRegistrationService $registrations): RedirectResponse
    {
        // Akun belum dibuat: data disimpan sebagai pending sampai kode OTP benar.
        $token = $request->session()->get(PendingRegistrationService::TOKEN_KEY) ?? Str::random(64);
        $pending = $registrations->start($request->only('name', 'email', 'phone', 'password'), $token);
        $request->session()->put(PendingRegistrationService::TOKEN_KEY, $token);

        $request->session()->put(PendingRegistrationService::SESSION_KEY, $pending->email);

        return redirect()->route('register.verify');
    }

    /**
     * Isi Syarat & Ketentuan dari settings, dengan default aman bila belum diisi.
     */
    private function terms(): string
    {
        $value = setting('content.terms');

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_string($decoded) ? $decoded : $value;
        }

        return is_string($value) && trim($value) !== '' ? $value : self::DEFAULT_TERMS;
    }
}
