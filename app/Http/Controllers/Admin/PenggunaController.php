<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PenggunaController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $query = User::withCount('bookings')->with(['roles', 'branch']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $users = $query->orderBy('name')->paginate(12)->withQueryString();

        $branches = Branch::query()->orderBy('name')->pluck('name', 'id');

        return view('admin.pengguna.index', compact('users', 'branches'));
    }

    public function store(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30|regex:/^[0-9+\-\s()]+$/',
            'password' => 'required|string|min:8|confirmed',
            'branch_id' => 'required|exists:branches,id',
        ], [
            'email.unique' => 'Email sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
            'branch_id.required' => 'Pilih cabang untuk admin.',
        ]);

        $admin = (new User)->forceFill($data + ['email_verified_at' => now(), 'is_active' => true]);
        $admin->save();
        $admin->assignRole(RoleSeeder::ADMIN);

        return back()->with('success', "Admin cabang {$admin->name} berhasil dibuat.");
    }

    public function setRole(Request $request, User $pengguna)
    {
        $data = $request->validate([
            'role' => 'required|'.RoleSeeder::VALIDATION_RULE,
            'branch_id' => 'required_if:role,'.RoleSeeder::ADMIN.'|nullable|exists:branches,id',
        ]);
        Gate::authorize('setRole', [$pengguna, $data['role']]);

        $pengguna->syncRoles([$data['role']]);
        $pengguna->update(['branch_id' => $data['role'] === RoleSeeder::ADMIN ? $data['branch_id'] : null]);

        return back()->with('success', 'Role pengguna berhasil diperbarui.');
    }

    public function toggleActive(User $pengguna)
    {
        Gate::authorize('toggleActive', $pengguna);

        $pengguna->update(['is_active' => ! $pengguna->is_active]);
        $status = $pengguna->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun {$pengguna->name} berhasil {$status}.");
    }
}
