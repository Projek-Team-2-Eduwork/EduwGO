<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('user');

        // Memicu email verifikasi (User implements MustVerifyEmail).
        event(new Registered($user));

        Auth::login($user);

        return redirect(route('verification.notice', absolute: false));
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
