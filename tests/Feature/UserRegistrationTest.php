<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_or_submit_the_form(): void
    {
        $this->get(route('users.create'))->assertRedirect(route('login'));
        $this->post(route('users.store'), $this->validData())->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_there_is_no_public_register_route(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_signed_in_users_can_open_the_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('<form method="POST" action="'.route('users.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('Add User');
    }

    public function test_a_valid_submission_creates_a_user_with_a_hashed_password(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('users.store'), $this->validData())
            ->assertRedirect(route('users.create'))
            ->assertSessionHas('status', 'New Admin was added and can now sign in.');

        $user = User::where('email', 'new@example.com')->firstOrFail();

        $this->assertSame('New Admin', $user->name);
        $this->assertNotSame('secret-pass-123', $user->password);
        $this->assertTrue(Hash::check('secret-pass-123', $user->password));
    }

    public function test_duplicate_emails_are_rejected(): void
    {
        $admin = User::factory()->create(['email' => 'new@example.com']);

        $this->actingAs($admin)
            ->from(route('users.create'))
            ->post(route('users.store'), $this->validData(['email' => 'NEW@example.com']))
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::count());
    }

    public function test_a_mismatched_confirmation_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('users.create'))
            ->post(route('users.store'), $this->validData(['password_confirmation' => 'something-else']))
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('password')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');

        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_invalid_submissions_do_not_create_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('users.create'))
            ->post(route('users.store'), ['name' => '', 'email' => 'not-an-email', 'password' => 'short', 'password_confirmation' => ''])
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors(['name', 'email', 'password', 'password_confirmation']);

        $this->assertSame(1, User::count());
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Admin',
            'email' => 'new@example.com',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ], $overrides);
    }
}
