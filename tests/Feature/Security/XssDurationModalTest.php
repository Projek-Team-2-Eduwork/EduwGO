<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use Tests\TestCase;

/**
 * Parameter ?start= & ?days= di detail kendaraan masuk ke atribut x-data (JavaScript).
 * Modal hanya dirender untuk pengguna login, jadi korban XSS adalah pengguna yang login.
 */
class XssDurationModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_nilai_start_normal_tetap_diteruskan_ke_modal(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->get(route('kendaraan.detail', ['vehicle' => $vehicle, 'start' => '2026-12-01T10:00', 'days' => 2]))
            ->assertOk()
            ->assertSee('bookingDurationModal('.$vehicle->id.', '.Js::from('2026-12-01T10:00')->toHtml().',', false)
            ->assertSee(', '.Js::from('2')->toHtml().', false)', false);
    }

    public function test_payload_xss_di_query_string_tidak_bisa_keluar_dari_string_javascript(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->actingAs(User::factory()->create());
        $payload = "');alert(document.cookie);//";

        $response = $this->get(route('kendaraan.detail', ['vehicle' => $vehicle, 'start' => $payload, 'days' => '1"><script>x</script>']))
            ->assertOk();

        // Kutip tunggal di-escape sebagai \u0027 (bukan &#039; yang didekode browser jadi ' lagi).
        $response->assertDontSee('&#039;);alert(document.cookie)', false);
        $response->assertDontSee("');alert(document.cookie)", false);
        $response->assertSee(Js::from($payload)->toHtml(), false);
        $response->assertDontSee('<script>x</script>', false);
    }

    public function test_parameter_array_tidak_menyebabkan_error(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->get(route('kendaraan.detail', $vehicle).'?start[]=a&days[]=b')
            ->assertOk()
            ->assertSee('bookingDurationModal('.$vehicle->id.', '.Js::from('')->toHtml().',', false);
    }
}
