<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleRequest;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\AvailabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Vehicle::class);

        $running = fn (Builder $q) => $q->whereIn('status', ['paid', 'rented'])
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now());

        $query = Vehicle::query()->with('type');
        $query->withExists(['bookings as is_rented' => $running]);

        // Filter Status
        if ($request->filled('status')) {
            if ($request->status === 'nonaktif') {
                $query->where('is_active', false);
            } elseif ($request->status === 'disewa') {
                $query->where('is_active', true)->whereHas('bookings', $running);
            } elseif ($request->status === 'tersedia') {
                $query->where('is_active', true)->whereDoesntHave('bookings', $running);
            }
        }

        // Filter Tipe
        if ($request->filled('tipe')) {
            $query->where('vehicle_type_id', $request->tipe);
        }

        // Search Keyword
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                    ->orWhere('plate_number', 'like', "%{$q}%");
            });
        }

        $vehicles = $query->orderBy('name')->paginate(12)->withQueryString();
        $types = VehicleType::orderBy('name')->pluck('name', 'id');

        return view('admin.kendaraan.index', compact('vehicles', 'types'));
    }

    public function create()
    {
        Gate::authorize('create', Vehicle::class);
        $types = VehicleType::orderBy('name')->pluck('name', 'id');

        return view('admin.kendaraan.create', compact('types'));
    }

    public function store(VehicleRequest $request)
    {
        Gate::authorize('create', Vehicle::class);

        $data = $request->validated();
        $data['slug'] = $this->generateSlug($data['name'], $data['plate_number']);

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadAndResizeImage($request->file('image'));
        }

        Vehicle::create($data);

        return redirect()->route('admin.kendaraan.index')->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function show(Vehicle $kendaraan, AvailabilityService $availabilityService)
    {
        Gate::authorize('view', $kendaraan);

        $kendaraan->load('type');

        $jadwal = $availabilityService->bookedRanges($kendaraan, now()->subDay(), now()->addDays(30));
        $bufferMenit = (int) setting('booking.buffer_minutes', 60);

        return view('admin.kendaraan.show', compact('kendaraan', 'jadwal', 'bufferMenit'));
    }

    public function edit(Vehicle $kendaraan)
    {
        Gate::authorize('update', $kendaraan);
        $types = VehicleType::orderBy('name')->pluck('name', 'id');

        return view('admin.kendaraan.edit', compact('kendaraan', 'types'));
    }

    public function update(VehicleRequest $request, Vehicle $kendaraan)
    {
        Gate::authorize('update', $kendaraan);

        $data = $request->validated();
        $data['slug'] = $this->generateSlug($data['name'], $data['plate_number'], $kendaraan->id);

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadAndResizeImage($request->file('image'), $kendaraan->image);
        }

        $kendaraan->update($data);

        return redirect()->route('admin.kendaraan.index')->with('success', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy(Vehicle $kendaraan)
    {
        Gate::authorize('delete', $kendaraan);

        if ($kendaraan->bookings()->exists()) {
            $kendaraan->delete(); // Soft delete
            $pesan = 'Kendaraan dihapus sementara (soft delete) karena memiliki riwayat pesanan.';
        } else {
            if ($kendaraan->image && str_starts_with($kendaraan->image, 'storage/vehicles/')) {
                Storage::disk('public')->delete(str_replace('storage/', '', $kendaraan->image));
            }
            $kendaraan->forceDelete(); // Hard delete
            $pesan = 'Kendaraan dihapus permanen beserta fotonya.';
        }

        return redirect()->route('admin.kendaraan.index')->with('success', $pesan);
    }

    /**
     * Memastikan slug unik.
     */
    private function generateSlug(string $name, string $plate, ?int $ignoreId = null): string
    {
        $base = Str::slug($name).'-'.Str::slug($plate);
        $slug = $base;
        while (Vehicle::withTrashed()->where('slug', $slug)->where('id', '!=', $ignoreId ?? 0)->exists()) {
            $slug = $base.'-'.Str::random(4);
        }

        return $slug;
    }

    /**
     * Mengubah ukuran gambar maks 1200px dan mengunggahnya.
     */
    private function uploadAndResizeImage($file, ?string $oldImagePath = null): string
    {
        $imageContents = file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($imageContents);

        if (! $image) {
            throw new \Exception('Gagal memproses gambar.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $max = 1200;

        if ($width > $max || $height > $max) {
            if ($width > $height) {
                $newWidth = $max;
                $newHeight = (int) ($height * ($max / $width));
            } else {
                $newHeight = $max;
                $newWidth = (int) ($width * ($max / $height));
            }

            $newImage = imagecreatetruecolor($newWidth, $newHeight);

            // Pertahankan transparansi PNG / WEBP
            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, ['png', 'webp'])) {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $newImage;
        }

        $ext = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $filename = uniqid('veh_').'_'.time().'.'.$ext;
        $relativePath = 'vehicles/'.$filename;

        $disk = Storage::disk('public');
        if (! $disk->exists('vehicles')) {
            $disk->makeDirectory('vehicles');
        }

        $absolutePath = $disk->path($relativePath);

        if ($ext === 'png') {
            imagepng($image, $absolutePath);
        } elseif ($ext === 'webp') {
            imagewebp($image, $absolutePath);
        } else {
            imagejpeg($image, $absolutePath, 85);
        }
        imagedestroy($image);

        // Hapus file lama jika ada (hanya jika tersimpan di folder storage, abaikan file seed images/)
        if ($oldImagePath && str_starts_with($oldImagePath, 'storage/vehicles/')) {
            $oldRelative = str_replace('storage/', '', $oldImagePath);
            if ($disk->exists($oldRelative)) {
                $disk->delete($oldRelative);
            }
        }

        return 'storage/'.$relativePath;
    }
}
