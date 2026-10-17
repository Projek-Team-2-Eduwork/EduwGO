<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchProfileRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Pengaturan cabang milik admin cabang: alamat, kontak/WA, jam operasional, media sosial.
 * Pengaturan toko global (brand, aturan sewa, S&K) tetap khusus superadmin.
 */
class BranchSettingController extends Controller
{
    public function edit()
    {
        $branch = auth()->user()->branch;

        // Superadmin tidak terikat cabang: kelola semua cabang lewat master cabang
        if ($branch === null) {
            return auth()->user()->isSuperadmin()
                ? redirect()->route('admin.cabang.index')
                : abort(403, 'Anda belum ditempatkan di cabang mana pun.');
        }

        Gate::authorize('updateProfile', $branch);

        return view('admin.pengaturan.cabang', ['branch' => $branch]);
    }

    public function update(BranchProfileRequest $request)
    {
        $branch = $request->user()->branch;
        abort_if($branch === null, 403, 'Anda belum ditempatkan di cabang mana pun.');

        Gate::authorize('updateProfile', $branch);

        $branch->update($request->branchData());

        return back()->with('success', 'Pengaturan cabang berhasil diperbarui.');
    }
}
