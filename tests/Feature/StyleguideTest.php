<?php

namespace Tests\Feature;

use Tests\TestCase;

class StyleguideTest extends TestCase
{
    public function test_styleguide_menampilkan_semua_komponen(): void
    {
        $response = $this->get('/styleguide');

        $response->assertOk();
        $response->assertSee('badge-status');
        $response->assertSee('Lunas');
        $response->assertSee('Sedang disewa');
        $response->assertSee('Honda Vario 160');
        $response->assertSee('Belum ada pesanan');
        $response->assertSee('Halaman 2', false);
    }
}
