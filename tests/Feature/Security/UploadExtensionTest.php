<?php

namespace Tests\Feature\Security;

use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Nama file dari klien (mis. "foto.html") tidak boleh menentukan ekstensi file
 * yang disimpan di disk publik.
 */
class UploadExtensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /** @var list<UploadedFile> File palsu asli dipegang agar file sementaranya tidak terhapus. */
    private array $sources = [];

    /**
     * File upload dengan isi asli tetapi nama dari klien diganti; MIME dibaca dari isi file.
     */
    private function renamed(UploadedFile $file, string $clientName): UploadedFile
    {
        $this->sources[] = $file;

        return new UploadedFile($file->getRealPath(), $clientName, null, null, true);
    }

    private function vehiclePayload(UploadedFile $image): array
    {
        $type = VehicleType::query()->firstOrCreate(['slug' => 'matic'], ['name' => 'Matic']);

        return [
            'name' => 'Honda Vario 160',
            'brand' => 'Honda',
            'vehicle_type_id' => $type->id,
            'plate_number' => 'N 1234 AB',
            'price_per_day' => 100000,
            'image' => $image,
            'is_active' => '1',
        ];
    }

    public function test_foto_kendaraan_png_tetap_tersimpan_sebagai_png(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.kendaraan.store'), $this->vehiclePayload(UploadedFile::fake()->image('vario.png', 800, 600)))
            ->assertRedirect(route('admin.kendaraan.index'));

        $image = Vehicle::firstOrFail()->image;
        $this->assertStringEndsWith('.png', $image);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $image));
    }

    public function test_foto_kendaraan_bernama_html_disimpan_dengan_ekstensi_gambar(): void
    {
        $this->actingAsAdmin();

        // Isi JPEG valid (lolos mimes), tetapi nama file dari klien ".html" (polyglot HTML).
        $file = $this->renamed(UploadedFile::fake()->image('shell.jpg', 800, 600), 'shell.html');
        $this->assertSame('html', $file->getClientOriginalExtension());

        $this->post(route('admin.kendaraan.store'), $this->vehiclePayload($file))
            ->assertRedirect(route('admin.kendaraan.index'));

        $image = Vehicle::firstOrFail()->image;
        $this->assertStringEndsWith('.jpg', $image);
        $this->assertEmpty(array_filter(
            Storage::disk('public')->allFiles('vehicles'),
            fn (string $path) => str_ends_with($path, '.html'),
        ));
    }

    public function test_favicon_bernama_html_disimpan_dengan_ekstensi_dari_isi_file(): void
    {
        $this->actingAsSuperadmin();

        $this->put(route('admin.pengaturan.update'), [
            'brand_name' => 'EduwGo',
            'contact_whatsapp' => '6281122334455',
            'booking_max_days' => 5,
            'booking_invoice_minutes' => 60,
            'booking_buffer_minutes' => 60,
            'brand_favicon' => $this->renamed(UploadedFile::fake()->image('favicon.png', 32, 32), 'favicon.html'),
        ])->assertSessionHasNoErrors();

        $files = Storage::disk('public')->allFiles('settings');
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.png', $files[0]);
        $this->assertSame('storage/'.$files[0], setting('brand.favicon'));
    }
}
