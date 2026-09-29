<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Registrasi butuh role 'user' sudah ada (lihat EG-6).
        Role::firstOrCreate(['name' => 'user']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ], $override);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')
            ->assertStatus(200)
            ->assertSee('Syarat &amp; Ketentuan', false)
            ->assertSee('Nomor WhatsApp');
    }

    public function test_new_users_can_register_with_phone_role_and_verification_email(): void
    {
        Notification::fake();

        $response = $this->post('/register', $this->payload());

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('081234567890', $user->phone);
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasVerifiedEmail());

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_phone_with_62_prefix_is_accepted(): void
    {
        $this->post('/register', $this->payload(['phone' => '6281234567890']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'phone' => '6281234567890']);
    }

    public function test_registration_fails_with_invalid_phone_format(): void
    {
        $this->post('/register', $this->payload(['phone' => '12345']))
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_fails_when_terms_not_accepted(): void
    {
        $this->post('/register', $this->payload(['terms' => null]))
            ->assertSessionHasErrors('terms');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }
}
