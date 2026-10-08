<?php

namespace Tests\Feature;

use App\Models\Setting;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_seeder_is_idempotent_and_populates_defaults()
    {
        // Run seeder 1
        $this->seed(SettingSeeder::class);
        $countAfterFirstSeed = Setting::count();

        // Run seeder 2
        $this->seed(SettingSeeder::class);
        $countAfterSecondSeed = Setting::count();

        // Bukti Idempoten: Baris tidak bertambah
        $this->assertEquals($countAfterFirstSeed, $countAfterSecondSeed);
        $this->assertGreaterThan(0, $countAfterFirstSeed);

        // Verifikasi helper
        $this->assertEquals('EduwGo', setting('brand.name'));
        $this->assertEquals(60, setting('booking.buffer_minutes'));

        // Verifikasi 6 poin terms
        $terms = setting('content.terms');
        $points = array_filter(explode("\n", str_replace("\r", "", $terms)));
        
        $this->assertCount(6, $points);
    }
}