<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Vehicle;
use App\Models\VehicleType;
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
            ->assertSee('Sewa motor harian')
            ->assertSee('Informasi cabang segera tersedia')
            ->assertDontSee('ambil di 0 cabang')
            ->assertDontSee('Cabang Belum Dibuka');
    }

    public function test_satu_cabang_aktif_ditampilkan_tanpa_menghitung_cabang_nonaktif(): void
    {
        Branch::factory()->create(['name' => 'Cabang Pilihan']);
        Branch::factory()->count(2)->create(['is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('ambil di Pilihan')
            ->assertSee('Cabang Pilihan')
            ->assertDontSee('Informasi cabang segera tersedia');
    }

    public function test_copy_beranda_menyesuaikan_penambahan_dan_penonaktifan_cabang(): void
    {
        $branches = collect(['Cabang Alfa', 'Cabang Beta', 'Cabang Gama'])->map(fn ($name) => Branch::factory()->create(['name' => $name]));
        Branch::factory()->create(['name' => 'Cabang Tertutup', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('ambil di Alfa, Beta atau Gama')
            ->assertDontSee('Cabang Tertutup');

        $branches->last()->update(['is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('ambil di Alfa atau Beta')
            ->assertDontSee($branches->last()->name);
    }

    public function test_hero_memakai_nama_aset_dan_alt_text_netral(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('images/home/hero-road.webp')
            ->assertSee('Jalan kota dengan pengendara motor dan gedung tinggi pada siang hari');
    }

    public function test_cabang_dan_peta_berasal_dari_data_aktif(): void
    {
        $branch = Branch::factory()->create([
            'name' => 'Cabang Sungai',
            'address' => 'Jl. Sungai No. 8',
            'operating_hours' => 'Setiap hari, 08.00–17.00',
        ]);
        Branch::factory()->create(['name' => 'Cabang Nonaktif', 'is_active' => false]);

        $html = $this->withCookie('branch', $branch->slug)->get('/')
            ->assertOk()
            ->assertSee('Jl. Sungai No. 8')
            ->assertSee('Setiap hari, 08.00–17.00')
            ->assertDontSee('Cabang Nonaktif')
            ->getContent();
        $document = new \DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//div[@class="home-branches"]/article')->length);
        $map = $xpath->query('//a[starts-with(@href,"https://www.google.com/maps/search/")]')->item(0);
        $this->assertStringContainsString(rawurlencode($branch->address), $map->getAttribute('href'));
    }

    public function test_accordion_syarat_menjawab_singkat_dan_menautkan_ketentuan(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $document = new \DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $terms = $xpath->query('//section[@id="pertanyaan-umum"]//details[1]')->item(0);
        $this->assertStringContainsString('KTP asli dan SIM C aktif', $terms->textContent);
        $this->assertSame(route('terms'), $xpath->query('.//a', $terms)->item(0)->getAttribute('href'));
    }

    public function test_toggle_tema_hanya_di_footer_dan_tipe_memakai_query_katalog(): void
    {
        $branch = Branch::factory()->create();
        $vehicle = Vehicle::factory()->create(['branch_id' => $branch->id]);
        Vehicle::factory()->create(['branch_id' => $branch->id, 'vehicle_type_id' => VehicleType::create(['name' => 'Sport', 'slug' => 'sport'])->id]);
        $html = $this->withCookie('branch', $branch->slug)->get('/')->assertOk()->getContent();
        $document = new \DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $this->assertSame(0, $xpath->query('//nav[@aria-label="Navigasi utama"]//*[@aria-label="Pilih tema tampilan"]')->length);
        $this->assertSame(1, $xpath->query('//footer//*[@aria-label="Pilih tema tampilan"]')->length);
        $link = $xpath->query('//nav[@aria-label="Filter tipe motor"]/a[contains(@href,"type='.$vehicle->vehicle_type_id.'")]')->item(0);
        parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
        $this->assertSame((string) $vehicle->vehicle_type_id, $query['type']);
        $this->assertSame($branch->slug, $query['cabang']);
    }

    public function test_chip_tipe_disembunyikan_bila_hanya_satu_tipe(): void
    {
        $branch = Branch::factory()->create();
        Vehicle::factory()->create(['branch_id' => $branch->id]);

        $this->withCookie('branch', $branch->slug)->get('/')
            ->assertOk()
            ->assertDontSee('Filter tipe motor');
    }
}
