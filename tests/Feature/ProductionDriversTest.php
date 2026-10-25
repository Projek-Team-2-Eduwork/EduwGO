<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Driver produksi (render.yaml): SESSION_DRIVER=cookie & CACHE_STORE=file. Sesi hanya hidup dari cookie
 * yang dibawa browser, jadi tiap test membawa cookie respons sebelumnya dan membuang state sesi di memori.
 */
class ProductionDriversTest extends TestCase
{
    use RefreshDatabase;

    private const ENV = ['SESSION_DRIVER' => 'cookie', 'CACHE_STORE' => 'file'];

    protected function setUp(): void
    {
        foreach (self::ENV as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $_SERVER[$key] = $value;
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (['SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array'] as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $_SERVER[$key] = $value;
        }
    }

    /**
     * Simulasikan request baru dari browser: hanya cookie yang tersisa dari respons sebelumnya.
     */
    private function nextRequestWithCookiesFrom(TestResponse $response): void
    {
        foreach ($response->headers->getCookies() as $cookie) {
            $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
        }

        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $this->app['auth']->forgetGuards();
    }

    public function test_driver_produksi_aktif_di_test_ini(): void
    {
        $this->assertSame('cookie', config('session.driver'));
        $this->assertSame('file', config('cache.default'));
    }

    public function test_login_bertahan_lewat_cookie_sesi(): void
    {
        $user = User::factory()->create(['email' => 'cookie@eduwgo.test']);

        $login = $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $login->assertRedirect();

        $this->nextRequestWithCookiesFrom($login);
        $this->get('/profil')->assertOk();
    }

    public function test_tanpa_cookie_sesi_tidak_login(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();

        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $this->app['auth']->forgetGuards();
        $this->get('/profil')->assertRedirect(route('login'));
    }

    public function test_rate_limiter_login_jalan_dengan_cache_file(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah'])->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNotEmpty(glob(storage_path('framework/cache/data/*/*/*')));
    }

    public function test_filter_booking_tersimpan_di_cookie_sesi(): void
    {
        Vehicle::factory()->create([
            'branch_id' => Branch::factory()->create()->id,
            'vehicle_type_id' => VehicleType::factory()->create()->id,
            'is_active' => true,
        ]);
        $start = now()->addDays(3)->setTime(9, 0)->format('Y-m-d\TH:i');

        $first = $this->get('/kendaraan?start_at='.$start.'&days=2');
        $first->assertOk();

        $this->nextRequestWithCookiesFrom($first);
        $this->get('/kendaraan')->assertOk()->assertViewHas('filter', fn ($filter) => ($filter['days'] ?? null) == 2);
    }
}
