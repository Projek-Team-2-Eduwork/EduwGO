<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);
    }

    public function test_url_acak_menampilkan_404_custom(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Kembali ke Home');
    }

    public function test_user_biasa_mengakses_admin_menampilkan_403_custom(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)->get('/admin/dashboard')
            ->assertForbidden()
            ->assertSee('Akses ditolak')
            ->assertSee('Kembali ke Home');
    }

    public function test_halaman_error_419_500_503_menampilkan_pesan_indonesia(): void
    {
        Route::get('/_uji-error/{code}', fn (int $code) => abort($code));

        foreach ([419 => 'Sesi telah berakhir', 500 => 'Terjadi kesalahan di server', 503 => 'Sedang dalam pemeliharaan'] as $code => $text) {
            $this->get("/_uji-error/{$code}")->assertStatus($code)->assertSee($text)->assertSee('Kembali ke Home');
        }
    }

    public function test_halaman_500_tetap_render_tanpa_query_database(): void
    {
        Route::get('/_uji-error-500', fn () => abort(500));

        DB::enableQueryLog();
        $this->get('/_uji-error-500')->assertStatus(500)->assertSee('Terjadi kesalahan di server');

        $this->assertSame([], DB::getQueryLog());
    }

    public function test_syarat_ketentuan_menampilkan_default_enam_poin_tanpa_settings(): void
    {
        $response = $this->get('/syarat-ketentuan');

        $response->assertOk()->assertSee('Syarat &amp; Ketentuan', false)->assertSee('1x24 jam')->assertSee('2 identitas');
        $this->assertSame(6, substr_count($response->getContent(), 'rounded-full text-sm font-semibold'));
    }

    public function test_title_syarat_ketentuan_tidak_double_encode(): void
    {
        $this->get('/syarat-ketentuan')->assertSee('<title>Syarat &amp; Ketentuan — EduwGo</title>', false);
    }

    public function test_tentang_menampilkan_default_aman_dan_link_footer(): void
    {
        $this->get('/tentang')
            ->assertOk()
            ->assertSee('Tentang EduwGo')
            ->assertSee('Jam operasional')
            ->assertSee(route('terms'), false)
            ->assertSee(route('about'), false);
    }

    public function test_seo_menghasilkan_meta_dari_props(): void
    {
        $html = Blade::render('<x-seo title="Vario 125" description="Motor matic irit" image="/img/vario.jpg" />');

        $this->assertStringContainsString('<title>Vario 125 — EduwGo</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Motor matic irit">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Vario 125 — EduwGo">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="Motor matic irit">', $html);
        $this->assertStringContainsString('<meta property="og:image" content="'.url('/img/vario.jpg').'">', $html);
    }

    public function test_seo_default_masuk_akal_dan_mengabaikan_html_pada_title(): void
    {
        $html = Blade::render('<x-seo title="<script>x</script>Judul" />');

        $this->assertStringContainsString('<title>xJudul — EduwGo</title>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('<meta name="description" content="EduwGo menyediakan rental motor', $html);
        $this->assertStringNotContainsString('og:image', $html);
    }

    public function test_halaman_dapat_mengatur_meta_lewat_section(): void
    {
        $this->get('/tentang')
            ->assertOk()
            ->assertSee('<title>Tentang Kami — EduwGo</title>', false)
            ->assertSee('<meta property="og:description" content="EduwGo menyediakan rental motor', false);
    }
}
