<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\VerifyEmailCode;
use App\Services\PendingRegistrationService;
use App\Services\XenditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditTimeAuthTest extends TestCase
{
    use RefreshDatabase;

    public static function starts(): array
    {
        return [['2030-01-02T03:00:00Z'], ['2030-01-02T10:00:00+07:00'], ['2030-01-02T10:00:00']];
    }

    #[DataProvider('starts')]
    public function test_booking_round_trip_uses_jakarta(string $start): void
    {
        $this->travelTo(now()->setDate(2030, 1, 1)->startOfDay());
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $this->mock(XenditService::class)->shouldReceive('createInvoice')->once()->andReturnUsing(fn ($booking) => Payment::factory()->create(['booking_id' => $booking->id]));
        $this->actingAs($user)->get(route('checkout.show', ['vehicle' => $vehicle->id, 'start' => $start, 'days' => 1]))
            ->assertOk()->assertViewHas('start', fn ($date) => $date->format('Y-m-d H:i:sP') === '2030-01-02 10:00:00+07:00');
        $this->post(route('checkout.store', $vehicle->id), [
            'start' => $start, 'days' => 1, 'customer_name' => 'Budi', 'customer_phone' => '081234567890',
        ])->assertSessionHasNoErrors();
        $booking = Booking::firstOrFail();
        $this->assertSame('2030-01-02 10:00:00', $booking->getRawOriginal('start_at'));
        $this->assertSame('2030-01-03 10:00:00', $booking->getRawOriginal('end_at'));
        $this->getJson(route('kendaraan.jadwal', ['vehicle' => $vehicle->id, 'month' => '2030-01']))
            ->assertJsonPath('days.2030-01-02', ['09:00–24:00']);
        $this->getJson('/api/kendaraan/'.$vehicle->id.'/durasi?'.http_build_query(['start' => $start]))
            ->assertOk()->assertJsonPath('1.available', false)
            ->assertJsonPath('1.end_at', '2030-01-03T10:00:00+07:00');
    }

    public static function activeStatuses(): array
    {
        return [['pending'], ['paid'], ['rented']];
    }

    #[DataProvider('activeStatuses')]
    public function test_active_booking_blocks_account_deletion(string $status): void
    {
        $user = User::factory()->create();
        Booking::factory()->create(['user_id' => $user->id, 'status' => $status]);
        $this->actingAs($user)->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email]);
    }

    public static function staffRoles(): array
    {
        return [['admin'], ['superadmin']];
    }

    #[DataProvider('staffRoles')]
    public function test_staff_cannot_delete_self(string $role): void
    {
        Role::findOrCreate($role);
        $user = User::factory()->create()->assignRole($role);
        $this->actingAs($user)->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');
        $this->assertNotNull($user->fresh());
    }

    public function test_deletion_keeps_booking_payment_and_history(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => BookingStatus::Returned]);
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);
        $history = $booking->histories()->create(['to_status' => 'returned', 'changed_by' => $user->id]);
        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertRedirect('/');
        $this->assertGuest();
        $this->assertNotNull($booking->fresh());
        $this->assertNotNull($payment->fresh());
        $this->assertNotNull($history->fresh());
        $this->assertFalse($user->fresh()->is_active);
        $this->assertNotSame($user->email, $user->fresh()->email);
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }

    private function registerPayload(): array
    {
        return ['name' => 'Budi', 'email' => 'budi@example.com', 'phone' => '081234567890', 'password' => 'password', 'password_confirmation' => 'password', 'terms' => '1'];
    }

    public function test_register_has_email_cooldown(): void
    {
        Notification::fake();
        $this->post('/register', $this->registerPayload())->assertSessionHasNoErrors();
        $hash = PendingRegistration::first()->code_hash;
        $this->post('/register', $this->registerPayload())->assertSessionHasErrors('email');
        $this->assertSame($hash, PendingRegistration::first()->code_hash);
    }

    public function test_another_session_cannot_replace_or_complete_pending_registration(): void
    {
        Notification::fake();
        $this->post('/register', $this->registerPayload());
        $pending = PendingRegistration::firstOrFail();
        $code = Notification::sent($pending, VerifyEmailCode::class)->last()->code;
        $this->flushSession();
        $this->travel(61)->seconds();
        $payload = array_replace($this->registerPayload(), ['password' => 'attacker123', 'password_confirmation' => 'attacker123']);
        $this->post('/register', $payload)->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $pending->fresh()->password));
        $this->withSession([PendingRegistrationService::SESSION_KEY => $pending->email])
            ->post(route('register.verify.store'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_restart_cannot_bypass_email_cooldown(): void
    {
        Notification::fake();
        $this->post('/register', $this->registerPayload())->assertSessionHasNoErrors();
        $this->delete(route('register.restart'))->assertRedirect();
        $this->post('/register', $this->registerPayload())->assertSessionHasErrors('email');
        $this->assertDatabaseCount('pending_registrations', 0);
    }

    public function test_email_hourly_limit_applies_to_register(): void
    {
        Notification::fake();
        $key = 'registration-code:'.hash('sha256', 'budi@example.com');
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit($key, 3600);
        }
        $this->post('/register', $this->registerPayload())->assertSessionHasErrors('email');
        $this->assertDatabaseCount('pending_registrations', 0);
        Notification::assertNothingSent();
    }

    public function test_invalid_booking_time_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->actingAs(User::factory()->create())->post(route('checkout.store', $vehicle->id), [
            'start' => 'not-a-date', 'days' => 1, 'customer_name' => 'Budi', 'customer_phone' => '081234567890',
        ])->assertSessionHasErrors('start');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_profile_update_is_throttled(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->patch('/profile', ['name' => 'Budi', 'email' => $user->email])->assertRedirect();
        }
        $this->patch('/profile', ['name' => 'Budi', 'email' => 'new@example.com'])->assertTooManyRequests();
    }
}
