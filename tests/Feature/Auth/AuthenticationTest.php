<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_see_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="remember"', false);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'test@example.com',
            'password' => 'Password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_credentials_show_generic_error(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->from(route('login'))->followingRedirects()->post(route('login.post'), [
            'email' => 'test@example.com',
            'password' => 'WrongPassword1',
        ]);

        $response->assertSee('These credentials do not match our records.');
        $this->assertGuest();
    }

    public function test_unknown_email_shows_same_generic_error(): void
    {
        $response = $this->from(route('login'))->followingRedirects()->post(route('login.post'), [
            'email' => 'nobody@example.com',
            'password' => 'WrongPassword1',
        ]);

        $response->assertSee('These credentials do not match our records.');
        $this->assertGuest();
    }

    public function test_sixth_failed_login_within_a_minute_is_blocked(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'Password123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->from(route('login'))->post(route('login.post'), [
                'email' => 'test@example.com',
                'password' => 'WrongPassword1',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->from(route('login'))->followingRedirects()->post(route('login.post'), [
            'email' => 'test@example.com',
            'password' => 'WrongPassword1',
        ]);

        $response->assertSee('Too many login attempts');
        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('login'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_authenticated_user_is_redirected_away_from_register(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('register'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_guest_visiting_dashboard_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = User::factory()->create();

        $response = $this->withSession(['foo' => 'bar'])
            ->actingAs($user)
            ->post(route('logout'));

        $response->assertRedirect(route('login'));
        $response->assertSessionMissing('foo');
        $this->assertGuest();
    }
}
