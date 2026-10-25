<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filter tanggal dashboard sebelumnya langsung masuk Carbon::parse → 500 bila tidak valid.
 */
class DashboardInputValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_tanggal_valid_tetap_tampil(): void
    {
        $this->actingAsSuperadmin();

        $this->get(route('admin.dashboard', ['dari' => '2026-10-01', 'sampai' => '2026-10-31']))
            ->assertOk();
    }

    public function test_filter_tanggal_tidak_valid_ditolak_validasi_bukan_500(): void
    {
        $this->actingAsSuperadmin();

        $this->from(route('admin.dashboard'))
            ->get(route('admin.dashboard', ['dari' => 'bukan-tanggal']))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHasErrors('dari');
    }
}
