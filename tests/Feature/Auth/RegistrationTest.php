<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
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

        Notification::assertSentTo($user, VerifyEmailCode::class);
    }

    public function test_phone_with_62_prefix_is_accepted(): void
    {
        $this->post('/register', $this->payload(['phone' => '6281234567890']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'phone' => '6281234567890']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validPhones(): array
    {
        return [
            'lokal 08' => ['081234567890'],
            'indonesia 62' => ['6281234567890'],
            'amerika serikat' => ['14155552671'],
            'inggris' => ['447911123456'],
            'minimal 8 digit' => ['12345678'],
            'maksimal 15 digit' => ['123456789012345'],
        ];
    }

    #[DataProvider('validPhones')]
    public function test_phone_with_digits_only_is_accepted(string $phone): void
    {
        $this->post('/register', $this->payload(['phone' => $phone]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'phone' => $phone]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPhones(): array
    {
        return [
            'terlalu pendek' => ['12345'],
            'terlalu panjang' => ['1234567890123456'],
            'ada huruf' => ['0812abc67890'],
            'ada spasi' => ['0812 3456 7890'],
            'ada tanda plus' => ['+14155552671'],
            'ada tanda minus' => ['0812-3456-7890'],
        ];
    }

    #[DataProvider('invalidPhones')]
    public function test_registration_fails_with_invalid_phone_format(string $phone): void
    {
        $this->post('/register', $this->payload(['phone' => $phone]))
            ->assertSessionHasErrors(['phone' => 'Nomor WhatsApp hanya boleh berisi angka (8-15 digit) tanpa spasi, tanda + atau - (contoh: 081234567890 atau 14155552671).']);

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
