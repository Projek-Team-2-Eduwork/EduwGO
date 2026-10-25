<?php

namespace Tests\Feature;

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

    public function test_home_server_render_starts_with_transparent_header(): void
    {
        $document = new \DOMDocument;
        $document->loadHTML($this->get('/')->assertOk()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $this->assertFalse($document->documentElement->hasAttribute('data-nav-pill'));
    }
}
