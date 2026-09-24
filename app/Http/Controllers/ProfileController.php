<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Status booking yang dihitung sebagai "aktif".
     *
     * @var list<string>
     */
    private const ACTIVE_STATUSES = ['pending', 'paid', 'rented'];

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'summary' => $this->bookingSummary($request->user()),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'social_account' => $this->socialAccount($data),
            'theme_preference' => $data['theme'] ?? $user->theme_preference ?? 'system',
        ]);

        if ($user->isDirty('email')) {
            // Email berubah: paksa verifikasi ulang + kirim link verifikasi baru.
            $user->email_verified_at = null;
            $user->save();

            $user->sendEmailVerificationNotification();

            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Simpan preferensi tema user (dipakai select di /profil & theme-toggle).
     *
     * PATCH /profil/tema -> { "theme": "light|dark|system" }
     */
    public function theme(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
        ]);

        $request->user()
            ->forceFill(['theme_preference' => $validated['theme']])
            ->save();

        return response()->json(['theme' => $validated['theme']]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Ringkasan booking milik user (tabel bookings dibuat di EG-4).
     *
     * @return array{total: int, active: int, has_bookings_table: bool}
     */
    private function bookingSummary(User $user): array
    {
        if (! Schema::hasTable('bookings')) {
            return ['total' => 0, 'active' => 0, 'has_bookings_table' => false];
        }

        $query = fn () => DB::table('bookings')->where('user_id', $user->id);

        return [
            'total' => $query()->count(),
            'active' => $query()->whereIn('status', self::ACTIVE_STATUSES)->count(),
            'has_bookings_table' => true,
        ];
    }

    /**
     * Susun kolom social_account dari field instagram/facebook.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>|null
     */
    private function socialAccount(array $data): ?array
    {
        $social = [];

        foreach (['instagram', 'facebook'] as $network) {
            $value = trim((string) ($data[$network] ?? ''));

            if ($value !== '') {
                $social[$network] = ltrim($value, '@');
            }
        }

        return $social === [] ? null : $social;
    }
}
