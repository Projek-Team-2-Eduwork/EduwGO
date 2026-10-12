<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LokasiRequest;
use App\Models\Location;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class LokasiController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Location::class);
        $lokasi = Location::withCount('vehicles')->orderBy('name')->get();

        return view('admin.lokasi.index', compact('lokasi'));
    }

    public function store(LokasiRequest $request)
    {
        Gate::authorize('create', Location::class);
        $slug = Str::slug($request->name);

        if (Location::where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'Lokasi dengan nama yang sama atau serupa sudah ada.']);
        }

        Location::create($request->validated());

        return back()->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function update(LokasiRequest $request, Location $lokasi)
    {
        Gate::authorize('update', $lokasi);

        // Slug TIDAK diregenerasi demi stabilitas (hook model hanya di event creating)
        $lokasi->update($request->validated());

        return back()->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Location $lokasi)
    {
        Gate::authorize('delete', $lokasi);

        if ($lokasi->vehicles()->exists()) {
            return back()->with('error', 'Gagal dihapus! Lokasi masih digunakan oleh beberapa unit kendaraan.');
        }

        $lokasi->delete();

        return back()->with('success', 'Lokasi berhasil dihapus.');
    }
}
