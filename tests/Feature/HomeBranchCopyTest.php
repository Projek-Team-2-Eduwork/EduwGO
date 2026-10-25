<?php

namespace Tests\Feature;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeBranchCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_tanpa_cabang_aktif_memakai_copy_fallback(): void
    {
        Branch::factory()->create(['name' => 'Cabang Belum Dibuka', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Sewa motor harian,')
            ->assertSee('Informasi cabang segera tersedia')
            ->assertDontSee('Tersedia di 0 cabang')
            ->assertDontSee('Cabang Belum Dibuka');
    }

    public function test_satu_cabang_aktif_ditampilkan_tanpa_menghitung_cabang_nonaktif(): void
    {
        Branch::factory()->create(['name' => 'Cabang Pilihan']);
        Branch::factory()->count(2)->create(['is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Tersedia di 1 cabang')
            ->assertSee('Cabang Pilihan')
            ->assertDontSee('Informasi cabang segera tersedia');
    }

    public function test_copy_beranda_menyesuaikan_penambahan_dan_penonaktifan_cabang(): void
    {
        $branches = Branch::factory()->count(3)->create();
        Branch::factory()->create(['name' => 'Cabang Tertutup', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Tersedia di 3 cabang')
            ->assertDontSee('Cabang Tertutup');

        $branches->last()->update(['is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Tersedia di 2 cabang')
            ->assertDontSee($branches->last()->name);
    }

    public function test_hero_memakai_nama_aset_dan_alt_text_netral(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('images/home/hero-road.webp')
            ->assertSee('images/home/hero-road-mobile.webp')
            ->assertSee('Jalan aspal terbuka berkelok di antara pepohonan dalam cahaya pagi');
    }
}
