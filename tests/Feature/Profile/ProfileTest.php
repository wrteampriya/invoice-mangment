<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_all_profile_routes(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->put(route('profile.update'))->assertRedirect(route('login'));
        $this->put(route('profile.password.update'))->assertRedirect(route('login'));
    }

    public function test_user_can_view_profile_page(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('Profile Settings');
    }

    public function test_user_can_update_name_and_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_duplicate_email_of_another_user_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Me',
            'email' => 'taken@example.com',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'me@example.com']);
    }

    public function test_keeping_own_email_is_allowed(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'New Name',
            'email' => 'me@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_photo_upload_works(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertSessionHasNoErrors();
        $path = $user->fresh()->profile_photo_path;
        $this->assertNotNull($path);
        $this->assertStringStartsWith('profile-photos/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_old_photo_is_deleted_on_replacement(): void
    {
        Storage::fake('public');
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('profile-photos', 'public');
        $user = User::factory()->create(['profile_photo_path' => $oldPath]);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->image('new.jpg'),
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($user->fresh()->profile_photo_path);
    }

    public function test_non_image_file_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('photo');
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_oversized_file_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->image('big.jpg')->size(3000),
        ]);

        $response->assertSessionHasErrors('photo');
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_remove_photo_deletes_file_and_clears_column(): void
    {
        Storage::fake('public');
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('profile-photos', 'public');
        $user = User::factory()->create(['profile_photo_path' => $oldPath]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'remove_photo' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_photo_menu_shows_remove_option_only_when_photo_exists(): void
    {
        $withoutPhoto = User::factory()->create(['profile_photo_path' => null]);

        $this->actingAs($withoutPhoto)->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('Select image')
            ->assertDontSee('Remove image');

        Storage::fake('public');
        $path = UploadedFile::fake()->image('avatar.jpg')->store('profile-photos', 'public');
        $withPhoto = User::factory()->create(['profile_photo_path' => $path]);

        $this->actingAs($withPhoto)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Select image')
            ->assertSee('Remove image');
    }

    public function test_user_can_never_modify_another_users_profile(): void
    {
        $user = User::factory()->create(['name' => 'Attacker', 'email' => 'attacker@example.com']);
        $other = User::factory()->create(['name' => 'Victim', 'email' => 'victim@example.com']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Hacked',
            'email' => 'hacked@example.com',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $other->id,
            'name' => 'Victim',
            'email' => 'victim@example.com',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Hacked',
            'email' => 'hacked@example.com',
        ]);
    }
}
