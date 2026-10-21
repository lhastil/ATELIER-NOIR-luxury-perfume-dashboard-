<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AdminLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_the_admin_layout(): void
    {
        $this->dashboard()
            ->assertSee('<title>Dashboard · ATELIER NOIR</title>', false)
            ->assertSee('href="#main-content"', false)
            ->assertSee('id="main-content"', false)
            ->assertSee('Atelier Noir')
            ->assertSee('Fragrance House')
            ->assertSee('<nav class="sidebar__nav" aria-label="Main">', false)
            ->assertSee('aria-label="Breadcrumb"', false);
    }

    public function test_sidebar_lists_every_section_in_order(): void
    {
        $this->dashboard()->assertSeeInOrder([
            'Dashboard', 'Products', 'Categories', 'Orders', 'Customers', 'Messages', 'My Profile', 'Store Settings',
        ]);
    }

    public function test_current_section_is_marked_as_the_current_page(): void
    {
        $html = $this->dashboard()->getContent();

        $this->assertSame(1, preg_match_all('#<a href="'.preg_quote(route('dashboard'), '#').'" class="nav-item"\s+aria-current="page"#', $html));
        $this->assertSame(1, preg_match_all('#class="nav-item"\s+aria-current="page"#', $html), 'Expected exactly one current nav item.');
        $this->assertStringContainsString('<span aria-current="page">Dashboard</span>', $html);
    }

    public function test_a_section_stays_current_on_its_sub_pages(): void
    {
        $this->defineRoutes(function () {
            Route::get('/products', fn () => view('dashboard'))->name('products.index');
            Route::get('/products/create', fn () => view('dashboard'))->name('products.create');
        });

        $html = $this->get('/products/create')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<a href="'.preg_quote(url('/products'), '#').'" class="nav-item"\s+aria-current="page"#', $html);
        $this->assertDoesNotMatchRegularExpression('#<a href="'.preg_quote(route('dashboard'), '#').'" class="nav-item"\s+aria-current#', $html);
    }

    public function test_sections_without_a_route_are_not_rendered_as_links(): void
    {
        $this->assertFalse(Route::has('products.index'));

        $this->dashboard()
            ->assertSee('<span class="nav-item" data-unavailable>', false)
            ->assertSee('Products<span class="visually-hidden"> (not available yet)</span>', false)
            ->assertDontSee('href="'.url('/products').'"', false);
    }

    public function test_drawer_controls_reference_the_sidebar(): void
    {
        $html = $this->dashboard()->getContent();

        $this->assertStringContainsString('id="app-sidebar"', $html);
        $this->assertMatchesRegularExpression('#<button[^>]*aria-controls="app-sidebar"[^>]*data-sidebar-open#', $html);
        $this->assertMatchesRegularExpression('#<button[^>]*aria-label="Close navigation"[^>]*data-sidebar-close#', $html);
        $this->assertSame(2, substr_count($html, 'data-drawer-inert'), 'Skip link and main region must become inert while the drawer is open.');
    }

    public function test_dropdowns_start_closed(): void
    {
        $html = $this->dashboard()->assertSee('There are no notifications.')->getContent();

        foreach (['notifications-panel', 'account-panel'] as $panel) {
            $this->assertMatchesRegularExpression('#<button[^>]*aria-expanded="false"[^>]*aria-controls="'.$panel.'"|<button[^>]*aria-controls="'.$panel.'"[^>]*aria-expanded="false"#', $html);
            $this->assertMatchesRegularExpression('#<div id="'.$panel.'"[^>]*data-dropdown-panel[^>]*hidden#', $html);
        }
    }

    public function test_breadcrumbs_link_parents_and_mark_the_current_page(): void
    {
        $this->blade('<x-admin.breadcrumbs :items="$items" />', ['items' => [
            ['label' => 'Dashboard', 'url' => '/'],
            ['label' => 'Products', 'url' => '/products'],
            ['label' => 'Edit'],
        ]])
            ->assertSee('<a class="breadcrumbs__link" href="/">Dashboard</a>', false)
            ->assertSee('<a class="breadcrumbs__link" href="/products">Products</a>', false)
            ->assertSee('<span aria-current="page">Edit</span>', false);
    }

    public function test_signed_in_user_sees_identity_and_logout_forms(): void
    {
        $user = User::factory()->create(['name' => 'Hélène Moreau', 'email' => 'helene@example.com']);

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Hélène Moreau')
            ->assertSee('helene@example.com')
            ->assertSee('HM')
            ->assertDontSee('Not signed in')
            ->getContent();

        $this->assertSame(2, substr_count($html, '<form method="POST" action="'.url('/logout').'">'), 'Sidebar and account menu each offer log out.');
        $this->assertSame(2, substr_count($html, 'name="_token"'));
    }

    public function test_user_supplied_text_is_escaped(): void
    {
        $user = User::factory()->create(['name' => '<script>alert(1)</script> Eve']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; Eve', false);
    }

    public function test_layout_renders_without_a_frontend_build(): void
    {
        // TestCase disables Vite, so a missing public/build must never break rendering in tests.
        $this->dashboard()->assertDontSee('ViteManifestNotFoundException');
    }

    /**
     * Registers routes for later phases (web middleware, refreshed name lookups),
     * so the layout can be tested against them.
     */
    private function defineRoutes(callable $routes): void
    {
        Route::middleware('web')->group($routes);
        Route::getRoutes()->refreshNameLookups();
    }

    private function dashboard(): TestResponse
    {
        return $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk();
    }
}
