<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileThemeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Halaman profil (route /profil sesuai spesifikasi EG-28) terbuka normal
     * walau tabel booking belum tersedia.
     */
    public function test_profile_page_is_displayed_without_bookings_table(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profil')
            ->assertOk()
            ->assertSee('Ringkasan Booking');
    }

    /**
     * Kriteria 1: update phone & sosmed tersimpan.
     */
    public function test_phone_and_social_accounts_are_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '0812-3456-7890',
                'instagram' => '@nastyo.ferdian',
                'facebook' => 'nastyoferdian',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('0812-3456-7890', $user->phone);
        $this->assertSame('nastyo.ferdian', $user->social_account['instagram'] ?? null);
        $this->assertSame('nastyoferdian', $user->social_account['facebook'] ?? null);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => 'bukan-nomor',
            ])
            ->assertSessionHasErrors('phone')
            ->assertRedirect('/profile');
    }

    /**
     * Kriteria 2: ubah email memaksa verifikasi ulang.
     */
    public function test_changing_email_forces_re_verification_and_resends_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => 'email-baru@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('email-baru@example.com', $user->email);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * Kriteria 3a: pilihan tema lewat form profil tersimpan.
     */
    public function test_theme_preference_is_saved_from_profile_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'theme' => 'dark',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('dark', $user->refresh()->theme_preference);
    }

    /**
     * Kriteria 3b: PATCH /profil/tema mengembalikan tema yang benar.
     */
    public function test_theme_endpoint_returns_and_persists_theme(): void
    {
        $user = User::factory()->create();

        foreach (['light', 'dark', 'system'] as $theme) {
            $this->actingAs($user)
                ->patchJson('/profil/tema', ['theme' => $theme])
                ->assertOk()
                ->assertJson(['theme' => $theme]);

            $this->assertSame($theme, $user->refresh()->theme_preference);
        }
    }

    public function test_theme_endpoint_rejects_unknown_theme(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/profil/tema', ['theme' => 'biru'])
            ->assertJsonValidationErrors('theme');
    }

    public function test_theme_endpoint_requires_authentication(): void
    {
        $this->patchJson('/profil/tema', ['theme' => 'dark'])
            ->assertUnauthorized();
    }
}
