<?php

namespace Tests\Feature;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarInitialStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_nonhome_server_render_starts_with_pill_before_javascript(): void
    {
        foreach (['/kendaraan', '/cabang', '/tentang'] as $url) {
            $document = new \DOMDocument;
            $document->loadHTML($this->get($url)->assertOk()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $this->assertTrue($document->documentElement->hasAttribute('data-nav-pill'));
        }
    }

    public function test_mobile_navigation_descends_from_navbar_with_branch_dropdown(): void
    {
        Branch::factory()->create();
        $document = new \DOMDocument;
        $document->loadHTML($this->get('/kendaraan')->assertOk()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);

        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi utama"]//div[contains(@class,"modal-from-navbar")]')->length);
        $this->assertSame(0, $xpath->query('//nav[@aria-label="Navigasi utama"]//div[@role="dialog" and @aria-label="Pilih cabang"]')->length);
        $this->assertGreaterThan(0, $xpath->query('//nav[@aria-label="Navigasi utama"]//div[contains(@class,"dropdown-panel")]')->length);
    }

    public function test_home_server_render_starts_with_solid_header(): void
    {
        $document = new \DOMDocument;
        $document->loadHTML($this->get('/')->assertOk()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $this->assertTrue($document->documentElement->hasAttribute('data-nav-pill'));
    }
}
