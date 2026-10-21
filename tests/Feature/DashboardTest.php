<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_header_shows_todays_date(): void
    {
        $this->travelTo('2026-03-14 09:00:00');

        $this->dashboard()->assertSee('<p class="eyebrow">Saturday 14 March 2026</p>', false);
    }

    public function test_signed_in_users_are_welcomed_back_by_name(): void
    {
        $user = User::factory()->create(['name' => 'Hélène Moreau']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('<h1 class="page-header__title font-display">Welcome back, Hélène Moreau</h1>', false);
    }

    public function test_sections_follow_the_specified_order(): void
    {
        $this->dashboard()->assertSeeInOrder([
            'Welcome',
            'Total Revenue', 'Total Orders', 'Products in Catalog', 'Total Customers',
            'Revenue', 'Order Analytics',
            'Recent Orders', 'Best Sellers',
        ]);
    }

    public function test_headings_form_a_logical_outline(): void
    {
        preg_match_all('#<(h[1-6])[^>]*>(.*?)</\1>#s', $this->mainContent(), $matches, PREG_SET_ORDER);

        $outline = array_map(fn (array $match) => $match[1].':'.trim(strip_tags($match[2])), $matches);

        $this->assertSame([
            'h1:Welcome back, Hélène Moreau',
            'h2:Key figures',
            'h2:Revenue',
            'h2:Order Analytics',
            'h2:Recent Orders',
            'h2:Best Sellers',
        ], $outline);
    }

    public function test_every_key_figure_is_an_announced_empty_value(): void
    {
        $html = $this->mainContent();

        foreach (['Total Revenue', 'Total Orders', 'Products in Catalog', 'Total Customers'] as $label) {
            $this->assertMatchesRegularExpression(
                '#role="group" aria-labelledby="(stat-[a-z-]+)">\s*<p id="\1" class="stat-card__label">'.preg_quote($label, '#').'</p>#',
                $html
            );
        }

        $this->assertSame(4, substr_count($html, '<span aria-hidden="true">—</span><span class="visually-hidden">No data</span>'));
    }

    public function test_no_business_figures_are_invented(): void
    {
        $html = $this->mainContent();

        // The date in the header is the only number on an empty dashboard.
        $text = strip_tags(preg_replace('#<p class="eyebrow">.*?</p>#s', '', $html));

        $this->assertDoesNotMatchRegularExpression('/\d/', $text);
        $this->assertDoesNotMatchRegularExpression('/[$€£¥]/u', $text);
        $this->assertStringNotContainsString('<canvas', $html);
    }

    public function test_every_panel_explains_its_empty_state(): void
    {
        $this->dashboard()
            ->assertSee('Revenue appears here once orders are delivered.')
            ->assertSee('Order activity appears here once orders are placed.')
            ->assertSee('New orders will be listed here.')
            ->assertSee('Best sellers appear here once orders exist.')
            ->assertDontSee('In preparation')
            ->assertDontSee('@endslot');
    }

    public function test_recent_orders_table_is_structured_but_empty(): void
    {
        $html = $this->mainContent();

        preg_match_all('#<th scope="col"[^>]*>(.*?)</th>#s', $html, $headers);
        $this->assertSame(
            ['Order', 'Customer', 'Date', 'Total', 'Status', 'Actions'],
            array_map(fn (string $cell) => trim(strip_tags($cell)), $headers[1])
        );

        $this->assertMatchesRegularExpression('#<tbody>\s*</tbody>#', $html);
        $this->assertStringContainsString('<caption id="table-recent-orders" class="visually-hidden">Recent orders</caption>', $html);
        $this->assertMatchesRegularExpression('#role="group" aria-labelledby="table-recent-orders" tabindex="0" data-scroll-region#', $html);
        $this->assertStringContainsString('class="table-region table-region--empty"', $html);
    }

    public function test_element_ids_are_unique(): void
    {
        preg_match_all('#\sid="([^"]+)"#', $this->dashboard()->getContent(), $ids);

        $this->assertNotEmpty($ids[1]);
        $this->assertSame([], array_keys(array_filter(array_count_values($ids[1]), fn (int $count) => $count > 1)));
    }

    public function test_every_labelling_reference_points_at_an_existing_element(): void
    {
        $html = $this->dashboard()->getContent();

        preg_match_all('#aria-(?:labelledby|controls)="([^"]+)"#', $html, $references);
        preg_match_all('#\sid="([^"]+)"#', $html, $ids);

        $this->assertSame([], array_values(array_diff($references[1], $ids[1])));
    }

    private function dashboard(): TestResponse
    {
        return $this->actingAs(User::factory()->create(['name' => 'Hélène Moreau']))->get(route('dashboard'))->assertOk();
    }

    private function mainContent(): string
    {
        preg_match('#<main[^>]*>(.*)</main>#s', $this->dashboard()->getContent(), $match);

        return $match[1];
    }
}
