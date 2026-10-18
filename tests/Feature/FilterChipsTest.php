<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterChipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_katalog_menampilkan_chip_tipe_dengan_pilihan_aktif_dan_picker_tanggal(): void
    {
        $matic = VehicleType::factory()->create(['name' => 'Matic']);
        VehicleType::factory()->create(['name' => 'Sport']);
        Vehicle::factory()->create(['vehicle_type_id' => $matic->id]);

        $this->get(route('kendaraan.index', ['type' => $matic->id]))
            ->assertOk()
            ->assertSee('name="type" value="'.$matic->id.'" checked', false)
            ->assertSee('name="type" value=""', false)
            ->assertSee('Sport')
            ->assertSee('data-picker', false)
            ->assertSee('Memuat kendaraan...');
    }

    public function test_halaman_cabang_memakai_chip_tipe_yang_sama(): void
    {
        $cabang = Branch::factory()->create(['slug' => 'pusat']);
        $type = VehicleType::factory()->create(['name' => 'Trail']);
        Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'branch_id' => $cabang->id]);

        $this->get(route('cabang.show', ['slug' => 'pusat', 'type' => $type->id]))
            ->assertOk()
            ->assertSee('name="type" value="'.$type->id.'" checked', false);
    }

    public function test_tipe_tidak_valid_ditolak_seperti_sebelumnya(): void
    {
        $this->get(route('kendaraan.index', ['type' => 99999]))
            ->assertSessionHasErrors('type');
    }
}
