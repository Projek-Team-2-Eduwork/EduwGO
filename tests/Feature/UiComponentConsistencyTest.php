<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class UiComponentConsistencyTest extends TestCase
{
    public function test_komponen_memakai_radius_dan_ukuran_tombol_seragam(): void
    {
        $alert = Blade::render('<x-alert type="success">ok</x-alert>');
        $this->assertStringContainsString('rounded-lg', $alert);
        $this->assertStringNotContainsString('rounded-md', $alert);

        foreach (['primary', 'secondary', 'danger'] as $variant) {
            $button = Blade::render("<x-{$variant}-button>Aksi</x-{$variant}-button>");
            $this->assertStringContainsString("btn-{$variant}-edu", $button);
            // padding berasal dari CSS pusat, bukan hardcode per komponen
            $this->assertDoesNotMatchRegularExpression('/\bp[xy]-\d/', $button);
        }

        $this->assertStringContainsString('rounded-lg', Blade::render('<x-text-input name="a" />'));
    }

    public function test_badge_status_semua_status_berbentuk_pil_seragam(): void
    {
        foreach (BookingStatus::cases() as $status) {
            $html = Blade::render('<x-badge-status :status="$status" />', ['status' => $status]);

            $this->assertStringContainsString('rounded-full', $html);
            $this->assertStringContainsString($status->label(), $html);
        }
    }

    public function test_badge_status_menolak_status_tidak_dikenal(): void
    {
        $this->expectException(\Throwable::class);

        Blade::render('<x-badge-status status="tidak-ada" />');
    }

    public function test_halaman_publik_tanpa_warna_gray_hardcode(): void
    {
        $html = $this->get('/styleguide')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/\b(?:bg|text|border)-gray-\d/', $html);
    }
}
