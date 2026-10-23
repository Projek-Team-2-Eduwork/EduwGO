<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_keamanan_dasar_terpasang_di_halaman_publik(): void
    {
        $this->get('/syarat-ketentuan')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy');
    }

    public function test_hsts_tidak_dikirim_lewat_http_di_luar_production(): void
    {
        $this->get('/syarat-ketentuan')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_dikirim_lewat_https_di_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('https://localhost/syarat-ketentuan')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
