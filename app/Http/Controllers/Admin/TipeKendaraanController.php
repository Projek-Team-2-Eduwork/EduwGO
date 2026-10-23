<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TipeKendaraanRequest;
use App\Models\VehicleType;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TipeKendaraanController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', VehicleType::class);
        $types = VehicleType::withCount('vehicles')->orderBy('name')->get();

        return view('admin.tipe-kendaraan.index', compact('types'));
    }

    public function store(TipeKendaraanRequest $request)
    {
        Gate::authorize('create', VehicleType::class);
        $slug = Str::slug($request->name);

        if (VehicleType::where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'Tipe kendaraan dengan nama yang sama atau serupa sudah ada.']);
        }

        VehicleType::create(['name' => $request->name, 'slug' => $slug]);

        return back()->with('success', 'Tipe kendaraan berhasil ditambahkan.');
    }

    public function update(TipeKendaraanRequest $request, VehicleType $tipe_kendaraan)
    {
        Gate::authorize('update', $tipe_kendaraan);

        // Slug TIDAK diregenerasi demi stabilitas filter URL katalog
        $tipe_kendaraan->update(['name' => $request->name]);

        return back()->with('success', 'Tipe kendaraan berhasil diperbarui.');
    }

    public function destroy(VehicleType $tipe_kendaraan)
    {
        Gate::authorize('delete', $tipe_kendaraan);

        if ($tipe_kendaraan->vehicles()->exists()) {
            return back()->with('error', 'Gagal dihapus! Tipe kendaraan masih digunakan oleh beberapa unit kendaraan.');
        }

        $tipe_kendaraan->delete();

        return back()->with('success', 'Tipe kendaraan berhasil dihapus.');
    }
}
