<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_or_submit_the_profile_forms(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->patch(route('profile.update'), ['name' => 'Changed', 'email' => 'changed@example.com'])->assertRedirect(route('login'));
        $this->put(route('profile.password.update'), $this->passwordData())->assertRedirect(route('login'));

        $user->refresh();
        $this->assertSame('Original Name', $user->name);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_signed_in_users_can_view_their_profile(): void
    {
        $user = User::factory()->create(['name' => 'Hasti Example', 'email' => 'hasti@example.com']);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('value="Hasti Example"', false)
            ->assertSee('value="hasti@example.com"', false)
            ->assertSee('<form method="POST" action="'.route('profile.update').'"', false)
            ->assertSee('<form method="POST" action="'.route('profile.password.update').'"', false)
            ->assertSee('name="current_password"', false)
            ->assertDontSee($user->password);
    }

    public function test_the_name_and_email_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), ['name' => 'New Name', 'email' => 'New@Example.com'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Your profile was updated.');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    public function test_only_the_signed_in_users_own_account_is_updated(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['name' => 'Other User']);

        $this->actingAs($user)
            ->patch(route('profile.update'), ['id' => $other->id, 'name' => 'New Name', 'email' => 'new@example.com'])
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('New Name', $user->fresh()->name);
        $this->assertSame('Other User', $other->fresh()->name);
    }

    public function test_the_users_current_email_is_accepted(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);

        $this->actingAs($user)
            ->patch(route('profile.update'), ['name' => 'New Name', 'email' => 'me@example.com'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('New Name', $user->fresh()->name);
    }

    public function test_another_users_email_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), ['name' => 'New Name', 'email' => 'TAKEN@example.com'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('email');

        $this->assertSame('me@example.com', $user->fresh()->email);
    }

    public function test_invalid_profile_submissions_do_not_change_the_account(): void
    {
        $user = User::factory()->create(['name' => 'Original Name', 'email' => 'me@example.com']);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), ['name' => '', 'email' => 'not-an-email'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors(['name', 'email']);

        $user->refresh();
        $this->assertSame('Original Name', $user->name);
        $this->assertSame('me@example.com', $user->email);
    }

    public function test_the_current_password_must_be_correct(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.password.update'), $this->passwordData(['current_password' => 'wrong-password']))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors(['current_password' => 'The current password is incorrect.']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_the_new_password_confirmation_must_match(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.password.update'), $this->passwordData(['password_confirmation' => 'something-else']))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_a_successful_change_stores_a_hashed_password_that_replaces_the_old_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.password.update'), $this->passwordData())
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Your password was changed.');

        $stored = $user->fresh()->password;
        $this->assertNotSame('new-secret-pass', $stored);
        $this->assertTrue(Hash::check('new-secret-pass', $stored));

        $this->assertFalse(Auth::validate(['email' => $user->email, 'password' => 'password']));
        $this->assertTrue(Auth::validate(['email' => $user->email, 'password' => 'new-secret-pass']));
    }

    public function test_password_fields_are_not_flashed_back_into_the_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('profile.edit'))
            ->put(route('profile.password.update'), $this->passwordData(['current_password' => 'wrong-password', 'password_confirmation' => 'mismatch']))
            ->assertSessionHasErrors(['current_password', 'password'])
            ->assertSessionMissing('_old_input.current_password')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');
    }

    /**
     * The factory's users have the password "password".
     *
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function passwordData(array $overrides = []): array
    {
        return array_merge([
            'current_password' => 'password',
            'password' => 'new-secret-pass',
            'password_confirmation' => 'new-secret-pass',
        ], $overrides);
    }
}
