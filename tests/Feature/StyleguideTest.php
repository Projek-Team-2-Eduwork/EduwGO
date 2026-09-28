<?php

namespace Tests\Feature;

use Tests\TestCase;

class StyleguideTest extends TestCase
{
    public function test_styleguide_tidak_muncul_di_luar_local(): void
    {
        // phpunit.xml set APP_ENV=testing, jadi route ini seharusnya tidak terdaftar.
        $response = $this->get('/styleguide');

        $response->assertNotFound();
    }
}
