<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatePickerCloseTest extends TestCase
{
    use RefreshDatabase;

    public function test_modal_durasi_memakai_picker_yang_menutup_saat_tanggal_diklik(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        $this->actingAs(User::factory()->create())
            ->get(route('kendaraan.detail', $vehicle))
            ->assertOk()
            ->assertSee('data-picker', false)
            ->assertSee('.flatpickr-day', false)
            ->assertSee('fp.close()', false)
            ->assertSee('fp.minuteElement', false);
    }

    public function test_tamu_tidak_mendapat_picker_modal_durasi(): void
    {
        $vehicle = Vehicle::factory()->create(['is_active' => true]);

        $this->get(route('kendaraan.detail', $vehicle))
            ->assertOk()
            ->assertDontSee('data-picker', false);
    }
}
