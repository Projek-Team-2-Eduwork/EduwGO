<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Master cabang, dikelola terpusat oleh superadmin.
 */
class CabangController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Branch::class);

        $cabang = Branch::withCount(['vehicles', 'admins'])->orderBy('name')->get();

        return view('admin.cabang.index', compact('cabang'));
    }

    public function store(BranchRequest $request)
    {
        Gate::authorize('create', Branch::class);

        if (Branch::where('slug', Str::slug($request->name))->exists()) {
            return back()->withErrors(['name' => 'Cabang dengan nama yang sama atau serupa sudah ada.'])->withInput();
        }

        Branch::create($request->branchData());

        return back()->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function update(BranchRequest $request, Branch $cabang)
    {
        Gate::authorize('update', $cabang);

        // Slug TIDAK diregenerasi demi stabilitas (hook model hanya di event creating)
        $cabang->update($request->branchData());

        return back()->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Branch $cabang)
    {
        Gate::authorize('delete', $cabang);

        if ($cabang->vehicles()->exists()) {
            return back()->with('error', 'Gagal dihapus! Cabang masih memiliki unit kendaraan.');
        }

        if ($cabang->admins()->exists()) {
            return back()->with('error', 'Gagal dihapus! Cabang masih memiliki admin. Pindahkan admin ke cabang lain dulu.');
        }

        $cabang->delete();

        return back()->with('success', 'Cabang berhasil dihapus.');
    }
}
