<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PenggunaController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $query = User::withCount('bookings')->with('roles');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $users = $query->orderBy('name')->paginate(12)->withQueryString();

        return view('admin.pengguna.index', compact('users'));
    }

    public function setRole(Request $request, User $pengguna)
    {
        $request->validate(['role' => 'required|in:admin,user']);
        Gate::authorize('setRole', [$pengguna, $request->role]);

        $pengguna->syncRoles([$request->role]);

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
