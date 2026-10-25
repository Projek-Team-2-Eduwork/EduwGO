<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHeroSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_hero_memakai_cabang_konteks_dan_parameter_katalog(): void
    {
        $branch = Branch::factory()->create(['slug' => 'pilihan']);
        Branch::factory()->create(['name' => 'Cabang Tutup', 'is_active' => false]);
        $html = $this->withCookie('branch', $branch->slug)->get('/')->assertOk()->getContent();
        $document = new \DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $form = $xpath->query('//form[@id="hero-search"]')->item(0);

        $this->assertNotNull($form);
        $this->assertSame('GET', $form->getAttribute('method'));
        $this->assertSame(route('kendaraan.index'), $form->getAttribute('action'));
        $start = $xpath->query('.//input[@name="start_at"]', $form)->item(0);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $start->getAttribute('value'));
        $this->assertTrue($start->hasAttribute('required'));
        $selected = $xpath->query('.//select[@name="cabang"]/option[@selected]', $form)->item(0);
        $this->assertSame($branch->slug, $selected->getAttribute('value'));
        $this->assertSame((int) setting('booking.max_days', 5), $xpath->query('.//select[@name="days"]/option', $form)->length);
        $this->assertStringNotContainsString('Cabang Tutup', $form->textContent);
        $this->assertStringContainsString('Cari motor', $form->textContent);
    }

    public function test_cari_motor_meneruskan_jadwal_dan_memilih_cabang_lewat_middleware(): void
    {
        $branch = Branch::factory()->create(['slug' => 'asal']);
        $target = Branch::factory()->create(['slug' => 'tujuan']);
        $vehicle = Vehicle::factory()->create(['branch_id' => $target->id, 'is_active' => true]);
        $other = Vehicle::factory()->create(['branch_id' => $branch->id, 'is_active' => true]);
        $start = now()->addDays(3)->startOfHour()->format('Y-m-d\TH:i');
        $url = route('kendaraan.index', ['cabang' => $target->slug, 'start_at' => $start, 'days' => 2]);

        $response = $this->withCookie('branch', $branch->slug)->get($url);
        $response->assertRedirect(route('kendaraan.index', ['start_at' => $start, 'days' => 2]));
        $this->assertSame($target->slug, session('branch'));
        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertViewHas('filter', fn ($filter) => $filter['start_at'] === $start && $filter['days'] === 2)
            ->assertSee($vehicle->name)
            ->assertDontSee($other->name);
    }
}
