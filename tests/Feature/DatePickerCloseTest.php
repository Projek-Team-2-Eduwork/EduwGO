<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatePickerCloseTest extends TestCase
{
    use RefreshDatabase;

    public function test_kartu_pesan_memakai_kalender_rentang_bukan_picker(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        $this->actingAs(User::factory()->create())
            ->get(route('kendaraan.detail', $vehicle))
            ->assertOk()
            ->assertSee('role="grid"', false)
            ->assertSee('data-jadwal-url="'.route('kendaraan.jadwal', $vehicle).'"', false)
            ->assertSee('data-max-days="5"', false)
            ->assertSee('Hapus tanggal')
            ->assertSee('Lanjut ke checkout')
            // Kalender read-only kolom kiri tidak dobel untuk user login.
            ->assertDontSee('Ketersediaan Unit')
            ->assertDontSee('data-picker', false);
    }

    public function test_tamu_tidak_mendapat_picker_modal_durasi(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        $this->get(route('kendaraan.detail', $vehicle))
            ->assertOk()
            ->assertDontSee('data-picker', false)
            ->assertSee('Ketersediaan Unit')
            ->assertSee('Masuk untuk pilih jadwal');
    }
}
