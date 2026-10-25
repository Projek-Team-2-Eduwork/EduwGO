<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_akun_memakai_panel_foto_dan_validasi_server(): void
    {
        foreach (['/login', '/register', '/forgot-password'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('images/home/hero-road.webp', false)
                // Tanpa tooltip bawaan browser berbahasa Inggris; pesan validasi dari server (Indonesia).
                ->assertSee('<form novalidate', false);
        }
    }

    public function test_login_kosong_ditolak_dengan_pesan_server(): void
    {
        $this->from('/login')->post('/login', [])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email', 'password']);
    }
}
