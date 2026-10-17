<?php

namespace Tests\Feature\Auth;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\VerifyEmailCode;
use App\Services\EmailVerificationCodeService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/verify-email')
            ->assertStatus(200)
            ->assertSee('Masukkan Kode Verifikasi');
    }

    public function test_verification_email_contains_six_digit_code(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertSessionHas('status', 'verification-code-sent');

        Notification::assertSentTo($user, VerifyEmailCode::class, function (VerifyEmailCode $notification) {
            return preg_match('/^\d{6}$/', $notification->code) === 1;
        });
    }

    public function test_verification_email_is_not_sent_to_verified_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('verification.send'));

        Notification::assertNothingSent();
    }

    public function test_email_can_be_verified_with_correct_code(): void
    {
        $user = User::factory()->unverified()->create();
        $code = app(EmailVerificationCodeService::class)->issue($user);

        Event::fake([Verified::class]);

        $this->actingAs($user)
            ->post(route('verification.verify'), ['code' => $code])
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertDatabaseMissing('email_verification_codes', ['user_id' => $user->id]);
    }

    public function test_code_is_stored_hashed(): void
    {
        $user = User::factory()->unverified()->create();
        $code = app(EmailVerificationCodeService::class)->issue($user);

        $this->assertNotSame($code, EmailVerificationCode::where('user_id', $user->id)->value('code_hash'));
    }

    public function test_email_is_not_verified_with_wrong_code(): void
    {
        $user = User::factory()->unverified()->create();
        app(EmailVerificationCodeService::class)->issue($user);

        $this->actingAs($user)
            ->post(route('verification.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_code_must_be_six_digits(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.verify'), ['code' => '12ab'])
            ->assertSessionHasErrors('code');
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $code = app(EmailVerificationCodeService::class)->issue($user);

        $this->travel(EmailVerificationCodeService::TTL_MINUTES + 1)->minutes();

        $this->actingAs($user)
            ->post(route('verification.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_code_is_invalidated_after_too_many_wrong_attempts(): void
    {
        $user = User::factory()->unverified()->create();
        $code = app(EmailVerificationCodeService::class)->issue($user);
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < EmailVerificationCodeService::MAX_ATTEMPTS; $i++) {
            $this->actingAs($user)->post(route('verification.verify'), ['code' => $wrong]);
        }

        // Kode benar pun sudah tidak berlaku setelah batas percobaan tercapai.
        $this->actingAs($user)
            ->post(route('verification.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resending_code_invalidates_previous_code(): void
    {
        $user = User::factory()->unverified()->create();
        $service = app(EmailVerificationCodeService::class);

        $old = $service->issue($user);
        do {
            $new = $service->issue($user);
        } while ($new === $old);

        $this->actingAs($user)
            ->post(route('verification.verify'), ['code' => $old])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
