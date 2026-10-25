<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuditResetHostTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_reset_link_uses_app_url_despite_forwarded_host(): void
    {
        Notification::fake();
        $this->app['env'] = 'production';
        config(['app.url' => 'https://eduwgo.example']);
        (new AppServiceProvider($this->app))->boot();
        $user = User::factory()->create();
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->withHeaders(['X-Forwarded-Host' => 'evil.example', 'X-Forwarded-Proto' => 'https'])
            ->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->assertStringStartsWith('https://eduwgo.example/reset-password/', $notification->toMail($user)->actionUrl);

            return true;
        });
    }

    public function test_local_links_keep_http_and_port(): void
    {
        $this->assertSame('http://localhost:8040/reset-password/token', url('http://localhost:8040/reset-password/token'));
        $this->get('http://localhost:8040/forgot-password')->assertOk();
        $this->assertSame('http://localhost:8040/reset-password/token', route('password.reset', ['token' => 'token']));
    }
}
