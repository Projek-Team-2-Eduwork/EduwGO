<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Rate limit register / lupa password / kirim ulang kode, dan respons lupa
 * password yang tidak membocorkan email terdaftar.
 */
class AuthRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'user']);
    }

    public function test_register_dibatasi_enam_kali_per_menit_per_ip(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            // Payload sengaja tidak valid: tidak membuat akun, tetapi tetap dihitung limiter.
            $this->post('/register', ['email' => "bukan-email-{$i}"])->assertStatus(302);
        }

        $this->post('/register', ['email' => 'lagi'])->assertStatus(429);
    }

    public function test_lupa_password_email_terdaftar_dan_tidak_terdaftar_mendapat_respons_sama(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $terdaftar = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
        $tidakTerdaftar = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'tidak-ada@example.com']);

        foreach ([$terdaftar, $tidakTerdaftar] as $response) {
            $response->assertRedirect('/forgot-password')
                ->assertSessionHasNoErrors()
                ->assertSessionHas('status', __('passwords.sent'));
        }

        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_lupa_password_dibatasi_per_menit(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/forgot-password', ['email' => 'a@example.com'])->assertStatus(302);
        }

        $this->post('/forgot-password', ['email' => 'a@example.com'])->assertStatus(429);
    }

    public function test_kirim_ulang_kode_dibatasi_per_menit(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)->post(route('verification.send'))
                ->assertSessionHas('status', 'verification-code-sent');
        }

        $this->actingAs($user)->post(route('verification.send'))->assertStatus(429);
    }

    public function test_kirim_ulang_kode_dibatasi_sepuluh_kali_per_jam(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        // 3 per menit selama 4 menit = 12 percobaan; hanya 10 yang lolos dalam satu jam.
        $lolos = 0;
        for ($menit = 0; $menit < 4; $menit++) {
            $this->travel(61)->seconds();

            for ($i = 0; $i < 3; $i++) {
                if ($this->actingAs($user)->post(route('verification.send'))->status() === 302) {
                    $lolos++;
                }
            }
        }

        $this->assertSame(10, $lolos);
    }
}
