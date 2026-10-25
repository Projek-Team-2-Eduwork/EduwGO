<?php

namespace Tests\Feature\Auth;

use App\Models\PendingRegistration;
use App\Models\User;
use App\Notifications\VerifyEmailCode;
use App\Services\EmailVerificationCodeService;
use App\Services\PendingRegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
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

        Notification::fake();
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

    /**
     * Kode terakhir yang dikirim ke pendaftar tertunda.
     */
    private function lastCode(string $email = 'test@example.com'): string
    {
        $pending = PendingRegistration::where('email', $email)->firstOrFail();

        return Notification::sent($pending, VerifyEmailCode::class)->last()->code;
    }

    private function wrongCode(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')
            ->assertStatus(200)
            ->assertSee('Syarat &amp; ketentuan', false)
            ->assertSee('Nomor WhatsApp');
    }

    public function test_register_stores_pending_and_sends_code_without_creating_user(): void
    {
        $this->post('/register', $this->payload())
            ->assertRedirect(route('register.verify', absolute: false))
            ->assertSessionHas(PendingRegistrationService::SESSION_KEY, 'test@example.com');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);

        $pending = PendingRegistration::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('081234567890', $pending->phone);
        $this->assertTrue(Hash::check('password', $pending->password));
        $this->assertNotSame('password', $pending->password);

        Notification::assertSentTo($pending, VerifyEmailCode::class, function (VerifyEmailCode $notification) use ($pending) {
            return preg_match('/^\d{6}$/', $notification->code) === 1
                && $notification->code !== $pending->code_hash;
        });
    }

    public function test_code_page_shows_pending_email_without_login(): void
    {
        $this->post('/register', $this->payload());

        $this->get(route('register.verify'))
            ->assertOk()
            ->assertSee('Masukkan Kode Verifikasi')
            ->assertSee('test@example.com')
            ->assertSee('Ubah email');
    }

    public function test_code_page_without_pending_session_redirects_to_register(): void
    {
        $this->get(route('register.verify'))
            ->assertRedirect(route('register', absolute: false))
            ->assertSessionHasErrors('email');
    }

    public function test_same_session_can_update_pending_after_cooldown(): void
    {
        $this->post('/register', $this->payload());
        $first = $this->lastCode();
        $this->travel(PendingRegistrationService::RESEND_COOLDOWN_SECONDS + 1)->seconds();

        $this->post('/register', $this->payload(['name' => 'Nama Baru', 'phone' => '6281111111111']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('register.verify', absolute: false));

        $this->assertDatabaseCount('pending_registrations', 1);
        $this->assertDatabaseHas('pending_registrations', ['email' => 'test@example.com', 'name' => 'Nama Baru', 'phone' => '6281111111111']);
        Notification::assertSentToTimes(PendingRegistration::first(), VerifyEmailCode::class, 2);

        // Kode dari pendaftaran pertama sudah hangus kecuali kebetulan sama.
        if ($first !== $this->lastCode()) {
            $this->post(route('register.verify.store'), ['code' => $first])->assertSessionHasErrors('code');
            $this->assertDatabaseCount('users', 0);
        }
    }

    public function test_correct_code_creates_verified_user_with_role_and_logs_in(): void
    {
        Event::fake([Registered::class, Verified::class]);

        $this->post('/register', $this->payload());

        $this->post(route('register.verify.store'), ['code' => $this->lastCode()])
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1')
            ->assertSessionMissing(PendingRegistrationService::SESSION_KEY);

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue($user->hasRole('user'));
        $this->assertSame('081234567890', $user->phone);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertDatabaseCount('pending_registrations', 0);

        Event::assertDispatched(Registered::class);
        Event::assertDispatched(Verified::class);
    }

    public function test_wrong_code_is_rejected_and_no_user_is_created(): void
    {
        $this->post('/register', $this->payload());

        $this->post(route('register.verify.store'), ['code' => $this->wrongCode($this->lastCode())])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertSame(1, PendingRegistration::first()->attempts);
    }

    public function test_expired_code_is_rejected_and_no_user_is_created(): void
    {
        $this->post('/register', $this->payload());
        $code = $this->lastCode();

        $this->travel(EmailVerificationCodeService::TTL_MINUTES + 1)->minutes();

        $this->post(route('register.verify.store'), ['code' => $code])
            ->assertSessionHasErrors(['code' => 'Kode sudah kedaluwarsa. Silakan kirim ulang kode.']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        // Data pendaftaran tetap ada supaya kode bisa dikirim ulang.
        $this->assertDatabaseHas('pending_registrations', ['email' => 'test@example.com']);
    }

    public function test_code_is_invalidated_after_too_many_wrong_attempts(): void
    {
        $this->post('/register', $this->payload());
        $code = $this->lastCode();

        for ($i = 0; $i < EmailVerificationCodeService::MAX_ATTEMPTS; $i++) {
            $this->post(route('register.verify.store'), ['code' => $this->wrongCode($code)]);
        }

        $this->post(route('register.verify.store'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_resend_sends_new_code_after_cooldown(): void
    {
        $this->post('/register', $this->payload());
        $pending = PendingRegistration::first();

        // Masih dalam jeda: ditolak, tidak ada email baru.
        $this->from(route('register.verify'))
            ->post(route('register.resend'))
            ->assertSessionHasErrors('code');
        Notification::assertSentToTimes($pending, VerifyEmailCode::class, 1);

        $this->travel(PendingRegistrationService::RESEND_COOLDOWN_SECONDS + 1)->seconds();

        $this->from(route('register.verify'))
            ->post(route('register.resend'))
            ->assertRedirect(route('register.verify', absolute: false))
            ->assertSessionHas('status', 'verification-code-sent');
        Notification::assertSentToTimes($pending, VerifyEmailCode::class, 2);

        // Kode baru langsung bisa dipakai.
        $this->post(route('register.verify.store'), ['code' => $this->lastCode()])
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_restart_removes_pending_and_returns_to_form_with_input(): void
    {
        $this->post('/register', $this->payload());

        $this->delete(route('register.restart'))
            ->assertRedirect(route('register', absolute: false))
            ->assertSessionHasInput('email', 'test@example.com')
            ->assertSessionMissing(PendingRegistrationService::SESSION_KEY);

        $this->assertDatabaseCount('pending_registrations', 0);
    }

    public function test_email_of_existing_user_is_rejected_on_register(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->post('/register', $this->payload())
            ->assertSessionHasErrors(['email' => 'Email sudah terdaftar.']);

        $this->assertDatabaseCount('pending_registrations', 0);
        Notification::assertNothingSent();
    }

    public function test_email_taken_while_pending_does_not_duplicate_user(): void
    {
        $this->post('/register', $this->payload());
        $code = $this->lastCode();

        // Email yang sama sempat menjadi user lewat jalur lain sebelum kode dimasukkan.
        User::factory()->create(['email' => 'test@example.com']);

        $this->post(route('register.verify.store'), ['code' => $code])
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHasErrors(['email' => PendingRegistrationService::ALREADY_REGISTERED]);

        $this->assertGuest();
        $this->assertSame(1, User::where('email', 'test@example.com')->count());
        $this->assertDatabaseCount('pending_registrations', 0);
    }

    public function test_abandoned_pending_is_pruned(): void
    {
        $this->post('/register', $this->payload());

        $this->travel(PendingRegistration::TTL_HOURS + 1)->hours();

        $this->artisan('model:prune', ['--model' => [PendingRegistration::class]])->assertSuccessful();

        $this->assertDatabaseCount('pending_registrations', 0);
    }

    public function test_phone_with_62_prefix_is_accepted(): void
    {
        $this->post('/register', $this->payload(['phone' => '6281234567890']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pending_registrations', ['email' => 'test@example.com', 'phone' => '6281234567890']);
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

        // Akun baru dibuat setelah kode OTP benar; nomor tersimpan dulu di pendaftaran tertunda.
        $this->assertDatabaseHas('pending_registrations', ['email' => 'test@example.com', 'phone' => $phone]);
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
        $this->assertDatabaseCount('pending_registrations', 0);
    }

    public function test_registration_fails_when_terms_not_accepted(): void
    {
        $this->post('/register', $this->payload(['terms' => null]))
            ->assertSessionHasErrors('terms');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('pending_registrations', 0);
    }
}
