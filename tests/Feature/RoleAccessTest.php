<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Which sections each role may open (User::SECTION_ROLES), and that navigation,
 * links, search and notifications follow the same rules.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Route names of each section's main page.
     */
    private const PAGES = [
        'dashboard', 'products.index', 'products.create', 'categories.index', 'categories.create', 'orders.index',
        'customers.index', 'messages.index', 'settings.edit', 'users.index', 'users.create', 'profile.edit',
    ];

    private const ALLOWED = [
        User::STORE_MANAGER => self::PAGES,
        User::ADMIN => [
            'dashboard', 'products.index', 'products.create', 'categories.index', 'categories.create',
            'orders.index', 'customers.index', 'messages.index', 'profile.edit',
        ],
        User::SELLER => ['products.index', 'products.create', 'orders.index', 'messages.index', 'profile.edit'],
    ];

    public function test_each_role_can_open_only_its_sections(): void
    {
        foreach (self::ALLOWED as $role => $allowed) {
            $user = User::factory()->create(['role' => $role]);

            foreach (self::PAGES as $page) {
                $this->actingAs($user)
                    ->get(route($page))
                    ->assertStatus(in_array($page, $allowed, true) ? 200 : 403);
            }
        }
    }

    public function test_every_role_can_use_its_own_profile(): void
    {
        foreach (array_keys(User::ROLES) as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('profile.edit'))->assertOk();
            $this->actingAs($user)
                ->patch(route('profile.update'), ['name' => "Renamed {$role}", 'email' => "{$role}@example.com"])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('profile.edit'));
            $this->actingAs($user)
                ->put(route('profile.password.update'), ['current_password' => 'password', 'password' => 'new-secret-pass', 'password_confirmation' => 'new-secret-pass'])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('profile.edit'));

            $this->assertSame("Renamed {$role}", $user->refresh()->name);
            $this->assertSame($role, $user->role);
        }
    }

    public function test_forbidden_actions_are_rejected_and_change_nothing(): void
    {
        $admin = User::factory()->admin()->create();
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create(['name' => 'Floral']);
        $customer = Customer::factory()->create();

        $this->actingAs($admin)->patch(route('settings.update'), ['store_name' => 'Hacked', 'contact_email' => 'x@example.com'])->assertForbidden();
        $this->assertDatabaseCount('store_settings', 0);

        $this->actingAs($seller)->post(route('categories.store'), ['name' => 'Woody'])->assertForbidden();
        $this->actingAs($seller)->put(route('categories.update', $category), ['name' => 'Changed'])->assertForbidden();
        $this->actingAs($seller)->delete(route('categories.destroy', $category))->assertForbidden();
        $this->actingAs($seller)->get(route('customers.show', $customer))->assertForbidden();

        $this->assertSame(['Floral'], Category::pluck('name')->all());
    }

    public function test_sellers_can_work_with_orders(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->seller()->create())
            ->patch(route('orders.status.update', $order), ['status' => 'shipped'])
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame('shipped', $order->refresh()->status);
    }

    public function test_the_sidebar_shows_only_each_roles_sections(): void
    {
        $links = [
            'dashboard' => 'dashboard', 'products.index' => 'products', 'categories.index' => 'categories',
            'orders.index' => 'orders', 'customers.index' => 'customers', 'messages.index' => 'messages',
            'profile.edit' => null, 'settings.edit' => 'settings', 'users.index' => 'users',
        ];

        foreach (array_keys(User::ROLES) as $role) {
            $user = User::factory()->create(['role' => $role]);
            $html = $this->actingAs($user)->get(route('profile.edit'))->assertOk()->getContent();
            preg_match('#<nav class="sidebar__nav".*?</nav>#s', $html, $nav);

            foreach ($links as $route => $section) {
                $visible = $section === null || $user->canAccessSection($section);
                $this->assertSame($visible, str_contains($nav[0], 'href="'.route($route).'"'), "{$role}: {$route}");
            }

            // No unavailable placeholders either: hidden sections are left out entirely.
            $this->assertStringNotContainsString('data-unavailable', $nav[0]);
        }
    }

    public function test_only_store_managers_see_store_settings_in_the_account_menu(): void
    {
        foreach ([User::STORE_MANAGER => true, User::ADMIN => false, User::SELLER => false] as $role => $visible) {
            $html = $this->actingAs(User::factory()->create(['role' => $role]))->get(route('profile.edit'))->getContent();
            preg_match('#<div id="account-panel".*?</div>\s*</div>#s', $html, $menu);

            $this->assertSame($visible, str_contains($menu[0], 'href="'.route('settings.edit').'"'), $role);
            $this->assertStringContainsString('href="'.route('profile.edit').'"', $menu[0]);
        }
    }

    public function test_each_role_signs_in_to_a_page_it_may_open(): void
    {
        foreach ([User::STORE_MANAGER => 'dashboard', User::ADMIN => 'dashboard', User::SELLER => 'products.index'] as $role => $home) {
            $user = User::factory()->create(['role' => $role, 'email' => "{$role}@example.com"]);

            $this->post(route('login'), ['email' => "{$role}@example.com", 'password' => 'password'])->assertRedirect(route($home));
            // Opening the login page while signed in goes to the same start page.
            $this->get(route('login'))->assertRedirect(route($home));

            $this->post(route('logout'));
        }
    }

    public function test_a_seller_who_first_opened_the_dashboard_signs_in_to_products(): void
    {
        User::factory()->seller()->create(['email' => 'seller@example.com']);

        // Signed out, the root address (the dashboard) sends them to the login page and is remembered.
        $this->get('/')->assertRedirect(route('login'));

        $this->post(route('login'), ['email' => 'seller@example.com', 'password' => 'password'])
            ->assertRedirect(route('products.index'))
            ->assertSessionMissing('url.intended');

        $this->get(route('products.index'))->assertOk();
    }

    public function test_after_login_each_role_returns_to_a_requested_page_only_if_it_may_open_it(): void
    {
        $cases = [
            // [role, page requested while signed out, where login should lead]
            [User::SELLER, 'orders.index', 'orders.index'],
            [User::SELLER, 'customers.index', 'products.index'],
            [User::SELLER, 'users.index', 'products.index'],
            [User::ADMIN, 'dashboard', 'dashboard'],
            [User::ADMIN, 'customers.index', 'customers.index'],
            [User::ADMIN, 'settings.edit', 'dashboard'],
            [User::STORE_MANAGER, 'dashboard', 'dashboard'],
            [User::STORE_MANAGER, 'users.index', 'users.index'],
        ];

        foreach ($cases as $index => [$role, $requested, $expected]) {
            User::factory()->create(['role' => $role, 'email' => "user{$index}@example.com"]);

            $this->get(route($requested))->assertRedirect(route('login'));
            $this->post(route('login'), ['email' => "user{$index}@example.com", 'password' => 'password'])
                ->assertRedirect(route($expected));
            $this->get(route($expected))->assertOk();

            $this->post(route('logout'));
        }
    }

    public function test_sellers_get_no_links_to_sections_they_cannot_open(): void
    {
        $customer = Customer::factory()->create(['name' => 'Clara Fontaine']);
        $order = Order::factory()->for($customer)->create();
        $seller = User::factory()->seller()->create();

        $html = $this->actingAs($seller)->get(route('orders.show', $order))->assertOk()->getContent();

        $this->assertStringContainsString('Clara Fontaine', $html);
        $this->assertStringNotContainsString('href="'.route('customers.show', $customer).'"', $html);
        // Brand links and breadcrumbs lead to the products list, not the dashboard.
        $this->assertStringNotContainsString('href="'.route('dashboard').'"', $html);
        $this->assertStringContainsString('<a class="brand" href="'.route('products.index').'">', $html);
        $this->assertStringContainsString('<a class="breadcrumbs__link" href="'.route('orders.index').'">Orders</a>', $html);

        // An Admin keeps the links.
        $this->actingAs(User::factory()->admin()->create())->get(route('orders.show', $order))
            ->assertSee('href="'.route('customers.show', $customer).'"', false)
            ->assertSee('<a class="breadcrumbs__link" href="'.route('dashboard').'">Dashboard</a>', false);
    }

    public function test_search_and_notifications_leave_out_forbidden_sections(): void
    {
        $this->travelTo('2026-10-21 12:00:00');
        $seller = User::factory()->seller()->create(['created_at' => '2026-10-01 00:00:00']);
        Category::factory()->create(['name' => 'Rose Family']);
        $customer = Customer::factory()->create(['name' => 'Rose Laurent', 'created_at' => '2026-10-20 12:00:00']);
        Order::factory()->for($customer)->create(['number' => 'ORD-ROSE1', 'created_at' => '2026-10-20 13:00:00']);

        $this->actingAs($seller)->get(route('search', ['q' => 'rose']))
            ->assertOk()
            ->mainContent()
            ->assertSee('ORD-ROSE1')
            ->assertDontSee('Rose Family')
            ->assertDontSee('<h2 class="search-results__heading">Customers</h2>', false)
            ->assertDontSee('<h2 class="search-results__heading">Categories</h2>', false);

        // The bell shows the new order but not the new customer.
        $this->actingAs($seller)->get(route('profile.edit'))
            ->assertSee('New order ORD-ROSE1 from Rose Laurent')
            ->assertDontSee('New customer Rose Laurent')
            ->assertSee('aria-label="Notifications, 1 unread"', false);

        // A Store Manager sees both.
        $this->actingAs(User::factory()->create(['created_at' => '2026-10-01 00:00:00']))->get(route('profile.edit'))
            ->assertSee('New customer Rose Laurent')
            ->assertSee('aria-label="Notifications, 2 unread"', false);
    }
}
