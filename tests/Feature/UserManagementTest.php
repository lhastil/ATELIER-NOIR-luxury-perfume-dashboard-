<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User Management: only Store Managers manage accounts and roles, and the
 * panel always keeps at least one Store Manager.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private const LAST_STORE_MANAGER = 'The panel must always have at least one Store Manager. Make another user a Store Manager first.';

    public function test_a_store_manager_sees_the_accounts_and_their_roles(): void
    {
        $manager = User::factory()->create(['name' => 'Anna Manager']);
        $seller = User::factory()->seller()->create(['name' => 'Sam Seller']);

        $html = $this->actingAs($manager)->get(route('users.index'))
            ->assertOk()
            ->assertSeeInOrder(['Anna Manager', 'Store Manager', 'Sam Seller', 'Seller'])
            ->assertSee('href="'.route('users.create').'"', false)
            ->assertSee('href="'.route('users.edit', $seller).'"', false)
            ->assertSee('action="'.route('users.destroy', $seller).'"', false)
            ->getContent();

        // No delete button for the signed-in account itself.
        $this->assertStringNotContainsString('action="'.route('users.destroy', $manager).'"', $html);
    }

    public function test_a_store_manager_can_add_a_user_with_a_role(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('<option value="seller"', false);

        $this->post(route('users.store'), [
            'name' => 'Sam Seller', 'email' => 'Sam@Example.com', 'role' => User::SELLER,
            'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('users.index'));

        $this->assertSame(User::SELLER, User::where('email', 'sam@example.com')->value('role'));
    }

    public function test_the_role_is_required_and_must_be_known(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['', 'owner', 'superuser'] as $role) {
            $this->from(route('users.create'))
                ->post(route('users.store'), [
                    'name' => 'New User', 'email' => 'new@example.com', 'role' => $role,
                    'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123',
                ])
                ->assertRedirect(route('users.create'))
                ->assertSessionHasErrors('role');
        }

        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_a_store_manager_can_edit_a_user_and_change_their_role(): void
    {
        $user = User::factory()->seller()->create(['name' => 'Sam Seller', 'email' => 'sam@example.com']);

        $html = $this->actingAs(User::factory()->create())
            ->get(route('users.edit', $user))
            ->assertOk()
            ->assertSee('value="Sam Seller"', false)
            ->assertDontSee('name="password"', false)
            ->getContent();

        $this->assertMatchesRegularExpression('#<option value="seller"\s+selected>Seller</option>#', $html);
        $this->assertDoesNotMatchRegularExpression('#<select\s+id="role"[^>]*\bdisabled\b#', $html);

        $this->put(route('users.update', $user), ['name' => 'Sam Admin', 'email' => 'sam@example.com', 'role' => User::ADMIN])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('status', 'Sam Admin was updated.');

        $user->refresh();
        $this->assertSame('Sam Admin', $user->name);
        $this->assertSame(User::ADMIN, $user->role);
    }

    public function test_passwords_cannot_be_set_from_the_edit_form(): void
    {
        $user = User::factory()->seller()->create();
        $hash = $user->password;

        $this->actingAs(User::factory()->create())
            ->put(route('users.update', $user), ['name' => $user->name, 'email' => $user->email, 'role' => User::SELLER, 'password' => 'new-secret-pass', 'password_confirmation' => 'new-secret-pass'])
            ->assertSessionHasErrors('password');

        $this->assertSame($hash, $user->refresh()->password);
    }

    public function test_a_store_manager_can_delete_another_user(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Ada Admin']);

        $this->actingAs(User::factory()->create())
            ->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('status', 'Ada Admin was deleted.');

        $this->assertModelMissing($user);
    }

    public function test_admins_and_sellers_cannot_manage_users_or_roles(): void
    {
        $target = User::factory()->seller()->create(['name' => 'Target']);

        foreach ([User::ADMIN, User::SELLER] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('users.create'))->assertForbidden();
            $this->actingAs($user)->get(route('users.edit', $target))->assertForbidden();
            $this->actingAs($user)->post(route('users.store'), [
                'name' => 'Sneaky', 'email' => "sneaky-{$role}@example.com", 'role' => User::STORE_MANAGER,
                'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123',
            ])->assertForbidden();
            // Promoting themselves or changing someone else's role directly.
            $this->actingAs($user)->put(route('users.update', $user), ['name' => $user->name, 'email' => $user->email, 'role' => User::STORE_MANAGER])->assertForbidden();
            $this->actingAs($user)->put(route('users.update', $target), ['name' => 'Changed', 'email' => $target->email, 'role' => User::ADMIN])->assertForbidden();
            $this->actingAs($user)->delete(route('users.destroy', $target))->assertForbidden();

            $this->assertSame($role, $user->refresh()->role);
        }

        $target->refresh();
        $this->assertSame('Target', $target->name);
        $this->assertSame(User::SELLER, $target->role);
        $this->assertDatabaseMissing('users', ['name' => 'Sneaky']);
    }

    public function test_the_profile_form_never_changes_a_role(): void
    {
        foreach ([User::ADMIN, User::SELLER] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->patch(route('profile.update'), ['name' => 'Same Name', 'email' => "{$role}@example.com", 'role' => User::STORE_MANAGER])
                ->assertRedirect(route('profile.edit'));

            $this->assertSame($role, $user->refresh()->role);
        }
    }

    public function test_the_profile_shows_the_role_read_only(): void
    {
        $html = $this->actingAs(User::factory()->seller()->create())->get(route('profile.edit'))
            ->assertSee('Only a Store Manager can change roles.')
            ->getContent();

        $this->assertMatchesRegularExpression('#<select\s+id="role"\s+name="role"[^>]*\bdisabled\b#', $html);
        $this->assertMatchesRegularExpression('#<option value="seller"\s+selected>Seller</option>#', $html);

        $manager = User::factory()->create();
        $html = $this->actingAs($manager)->get(route('profile.edit'))
            ->assertSee('href="'.route('users.edit', $manager).'"', false)
            ->getContent();

        $this->assertMatchesRegularExpression('#<select\s+id="role"\s+name="role"[^>]*\bdisabled\b#', $html);
    }

    public function test_the_last_store_manager_cannot_give_up_the_role(): void
    {
        $manager = User::factory()->create();
        User::factory()->admin()->create();

        $this->actingAs($manager)
            ->from(route('users.edit', $manager))
            ->put(route('users.update', $manager), ['name' => $manager->name, 'email' => $manager->email, 'role' => User::ADMIN])
            ->assertRedirect(route('users.edit', $manager))
            ->assertSessionHasErrors(['role' => self::LAST_STORE_MANAGER]);

        $this->assertSame(User::STORE_MANAGER, $manager->refresh()->role);
    }

    public function test_the_last_store_manager_cannot_be_deleted(): void
    {
        $manager = User::factory()->create();

        $this->actingAs($manager)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $manager))
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors(['user' => self::LAST_STORE_MANAGER]);

        $this->assertModelExists($manager);

        // The message is shown on the list.
        $this->actingAs($manager)->from(route('users.index'))->followingRedirects()
            ->delete(route('users.destroy', $manager))
            ->assertSee('<div class="form-alert" role="alert">', false)
            ->assertSee('The panel must always have at least one Store Manager.');
    }

    public function test_with_another_store_manager_a_manager_can_step_down(): void
    {
        $manager = User::factory()->create();
        User::factory()->create();

        // No longer a Store Manager, so they are sent to their new start page.
        $this->actingAs($manager)
            ->put(route('users.update', $manager), ['name' => $manager->name, 'email' => $manager->email, 'role' => User::ADMIN])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(User::ADMIN, $manager->refresh()->role);
        $this->actingAs($manager)->get(route('users.index'))->assertForbidden();
    }

    public function test_store_managers_cannot_delete_their_own_account(): void
    {
        $manager = User::factory()->create();
        User::factory()->create();

        $this->actingAs($manager)
            ->delete(route('users.destroy', $manager))
            ->assertSessionHasErrors(['user' => 'You cannot delete your own account. Another Store Manager can delete it.']);

        $this->assertModelExists($manager);
    }

    public function test_a_store_manager_can_remove_another_store_manager(): void
    {
        $manager = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($manager)->delete(route('users.destroy', $other))->assertRedirect(route('users.index'));

        $this->assertModelMissing($other);
    }
}
