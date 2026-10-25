<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AvifImageTest extends TestCase
{
    public function test_foto_statis_punya_sumber_avif_dengan_fallback_asli(): void
    {
        $html = Blade::render('<x-picture src="images/vehicles/honda-beat.jpg" alt="Beat" />');

        $this->assertStringContainsString('type="image/avif" srcset="'.asset('images/vehicles/honda-beat.avif').'"', $html);
        $this->assertStringContainsString('<img src="'.asset('images/vehicles/honda-beat.jpg').'" alt="Beat"', $html);
        $this->assertFileExists(public_path('images/home/hero-road.avif'));
    }

    public function test_upload_admin_tanpa_avif_tetap_img_biasa(): void
    {
        $html = Blade::render('<x-picture src="storage/vehicles/upload.jpg" alt="" />');

        $this->assertStringNotContainsString('image/avif', $html);
        $this->assertStringContainsString(asset('storage/vehicles/upload.jpg'), $html);
    }
}
