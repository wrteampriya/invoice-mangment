<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_changed_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword1']);

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'OldPassword1',
            'password' => 'NewPassword2',
            'password_confirmation' => 'NewPassword2',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('password_status');
        $this->assertTrue(Hash::check('NewPassword2', $user->fresh()->password));
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword1']);

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'WrongPassword9',
            'password' => 'NewPassword2',
            'password_confirmation' => 'NewPassword2',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('OldPassword1', $user->fresh()->password));
    }

    public function test_password_change_requires_confirmed_strong_password(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword1']);

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'OldPassword1',
            'password' => 'weak',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('OldPassword1', $user->fresh()->password));
    }

    public function test_only_error_message_shows_after_failed_password_change(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('id="password-hint" class="field-error d-none"', false);

        $this->actingAs($user)->from(route('profile.edit'))->put(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('The password field must be at least 8 characters.', false)
            ->assertSee('id="password-hint" class="field-error d-none"', false);
    }

    public function test_password_fields_keep_typed_values_after_failed_change(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('profile.edit'))->put(route('profile.password.update'), [
            'current_password' => 'WrongPassword9',
            'password' => 'NewPassword2',
            'password_confirmation' => 'NewPassword2',
        ])->assertSessionHasErrors('current_password');

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('value="WrongPassword9"', false);
        $response->assertSee('value="NewPassword2"', false);
    }

    public function test_new_password_must_differ_from_current_password(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword1']);

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'OldPassword1',
            'password' => 'OldPassword1',
            'password_confirmation' => 'OldPassword1',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('OldPassword1', $user->fresh()->password));
    }
}
