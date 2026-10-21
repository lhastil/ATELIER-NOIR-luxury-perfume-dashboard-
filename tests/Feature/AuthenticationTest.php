<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_admin_pages_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_the_login_page_is_shown_to_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<form method="POST" action="'.route('login').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('autocomplete="current-password"', false);
    }

    public function test_signed_in_users_are_sent_from_login_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_valid_credentials_sign_the_user_in(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->post(route('login'), ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_login_returns_to_the_page_that_was_requested(): void
    {
        // A protected page other than the dashboard, so the default redirect cannot pass this test.
        Route::middleware(['web', 'auth'])->get('/test-intended', fn () => 'intended page');
        User::factory()->create(['email' => 'admin@example.com']);

        $this->get('/test-intended')->assertRedirect(route('login'));

        $this->post(route('login'), ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertRedirect(url('/test-intended'));

        $this->get('/test-intended')->assertOk()->assertSee('intended page');
    }

    public function test_a_successful_login_regenerates_the_session(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->get(route('login'));
        $before = session()->getId();

        $this->post(route('login'), ['email' => 'admin@example.com', 'password' => 'password']);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->from(route('login'))
            ->post(route('login'), ['email' => 'admin@example.com', 'password' => 'wrong-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.failed')])
            ->assertSessionHasInput('email', 'admin@example.com')
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_email_and_password_are_required(): void
    {
        $this->from(route('login'))
            ->post(route('login'), ['email' => 'not-an-email', 'password' => ''])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_repeated_failures_are_throttled(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login'), ['email' => 'admin@example.com', 'password' => 'wrong-password']);
        }

        // Even the correct password is refused while the limit is in force.
        $this->post(route('login'), ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringStartsWith('Too many login attempts.', session('errors')->first('email'));
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'));
        $token = session()->token();

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNotSame($token, session()->token());
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_the_demo_seeder_creates_one_hashed_account(): void
    {
        $this->seed(DemoAdminSeeder::class);
        $this->seed(DemoAdminSeeder::class);

        $this->assertSame(1, User::where('email', DemoAdminSeeder::EMAIL)->count());
        $this->assertNotSame(DemoAdminSeeder::PASSWORD, User::first()->password);

        $this->post(route('login'), ['email' => DemoAdminSeeder::EMAIL, 'password' => DemoAdminSeeder::PASSWORD])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_the_demo_seeder_does_nothing_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->seed(DemoAdminSeeder::class);

        $this->assertSame(0, User::count());
    }
}
