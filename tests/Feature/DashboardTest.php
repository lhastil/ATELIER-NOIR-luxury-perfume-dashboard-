<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_an_empty_database_shows_zero_figures_and_empty_states(): void
    {
        $html = $this->mainContent();

        foreach (['Total Revenue' => '€0.00', 'Total Orders' => '0', 'Products in Catalog' => '0', 'Total Customers' => '0'] as $label => $value) {
            $this->assertMatchesRegularExpression(
                '#<p id="(stat-[a-z-]+)" class="stat-card__label">'.preg_quote($label, '#').'</p>\s*<p class="stat-card__value">'.preg_quote($value, '#').'</p>#',
                $html
            );
        }

        $this->assertStringContainsString('No delivered orders yet', $html);
        $this->assertStringContainsString('No revenue in the last six months', $html);
        $this->assertStringContainsString('Order activity appears here once orders are placed.', $html);
        $this->assertStringContainsString('New orders will be listed here.', $html);
        $this->assertStringContainsString('Best sellers appear here once orders are delivered.', $html);
        $this->assertStringNotContainsString('<canvas', $html);
        $this->assertStringNotContainsString('View all orders', $html);
        $this->assertMatchesRegularExpression('#<tbody>\s*</tbody>#', $html);
    }

    public function test_recent_orders_table_keeps_its_structure(): void
    {
        $html = $this->mainContent();

        preg_match_all('#<th scope="col"[^>]*>(.*?)</th>#s', $html, $headers);
        $this->assertSame(
            ['Order', 'Customer', 'Date', 'Total', 'Status', 'Actions'],
            array_map(fn (string $cell) => trim(strip_tags($cell)), $headers[1])
        );

        $this->assertStringContainsString('<caption id="table-recent-orders" class="visually-hidden">Recent orders</caption>', $html);
        $this->assertMatchesRegularExpression('#role="group" aria-labelledby="table-recent-orders" tabindex="0" data-scroll-region#', $html);
    }

    public function test_revenue_counts_only_delivered_orders_in_cents(): void
    {
        $this->travelTo('2026-10-21 12:00:00');

        $this->orderWith('delivered', '2026-10-05', [['84.50', 2], ['0.10', 3]]);
        $this->orderWith('delivered', '2026-08-10', [['128.00', 1]]);
        $this->orderWith('delivered', '2025-12-01', [['50.00', 1]]);

        foreach (['pending', 'processing', 'shipped', 'cancelled'] as $status) {
            $this->orderWith($status, '2026-10-10', [['1000.00', 1]]);
        }

        $html = $this->mainContent();

        // 169.30 + 128.00 + 50.00: every delivered order, also those older than the chart.
        $this->assertMatchesRegularExpression('#Total Revenue</p>\s*<p class="stat-card__value">€347.30</p>\s*<p class="stat-card__hint">From 3 delivered orders</p>#', $html);
        $this->assertMatchesRegularExpression('#Total Orders</p>\s*<p class="stat-card__value">7</p>#', $html);
    }

    public function test_the_revenue_chart_covers_six_months_with_empty_months_as_zero(): void
    {
        $this->travelTo('2026-10-21 12:00:00');

        $this->orderWith('delivered', '2026-10-05', [['84.50', 2], ['0.10', 3]]);
        $this->orderWith('delivered', '2026-07-31 23:59:59', [['128.00', 1]]);
        $this->orderWith('delivered', '2026-05-01 00:00:00', [['20.00', 2]]);
        $this->orderWith('delivered', '2026-04-30 23:59:59', [['500.00', 1]]);
        $this->orderWith('shipped', '2026-09-15', [['999.00', 1]]);

        $chart = $this->chart('currency');

        $this->assertSame('EUR', $chart['currency']);
        $this->assertSame(['May 2026', 'Jun 2026', 'Jul 2026', 'Aug 2026', 'Sep 2026', 'Oct 2026'], $chart['labels']);
        $this->assertSame([4000, 0, 12800, 0, 0, 16930], $chart['values']);

        // The same figures as a table for screen readers.
        preg_match('#<caption>Delivered revenue per month</caption>.*?</table>#s', $this->mainContent(), $table);
        preg_match_all('#<tr><th scope="row">(.*?)</th><td[^>]*>(.*?)</td></tr>#', $table[0], $rows, PREG_SET_ORDER);

        $this->assertSame(
            ['May 2026: €40.00', 'Jun 2026: €0.00', 'Jul 2026: €128.00', 'Aug 2026: €0.00', 'Sep 2026: €0.00', 'Oct 2026: €169.30'],
            array_map(fn (array $row) => $row[1].': '.$row[2], $rows)
        );
    }

    public function test_the_revenue_window_is_correct_at_the_end_of_a_month(): void
    {
        $this->travelTo('2026-08-31 23:30:00');

        $this->orderWith('delivered', '2026-03-01 00:00:00', [['10.00', 1]]);
        $this->orderWith('delivered', '2026-02-28 23:59:59', [['99.00', 1]]);

        $chart = $this->chart('currency');

        $this->assertSame(['Mar 2026', 'Apr 2026', 'May 2026', 'Jun 2026', 'Jul 2026', 'Aug 2026'], $chart['labels']);
        $this->assertSame([1000, 0, 0, 0, 0, 0], $chart['values']);
    }

    public function test_order_analytics_counts_every_status_in_a_fixed_order(): void
    {
        foreach (['delivered', 'pending', 'delivered', 'cancelled', 'pending', 'shipped'] as $status) {
            Order::factory()->create(['status' => $status]);
        }

        $chart = $this->chart('count');

        $this->assertTrue($chart['horizontal']);
        $this->assertSame(['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'], $chart['labels']);
        $this->assertSame([2, 0, 1, 2, 1], $chart['values']);
        $this->assertSame(['--color-accent', '--color-accent', '--color-accent', '--color-success', '--color-danger'], $chart['colors']);
    }

    public function test_recent_orders_are_the_five_newest_with_a_stable_order(): void
    {
        $customer = Customer::factory()->create(['name' => 'Clara Fontaine']);
        Order::factory()->for($customer)->create(['number' => 'ORD-10001', 'placed_at' => '2026-10-01 10:00:00']);
        Order::factory()->for($customer)->create(['number' => 'ORD-10002', 'placed_at' => '2026-10-02 10:00:00']);
        Order::factory()->for($customer)->create(['number' => 'ORD-10003', 'placed_at' => '2026-10-05 10:00:00']);
        Order::factory()->for($customer)->create(['number' => 'ORD-10004', 'placed_at' => '2026-10-05 10:00:00']);
        Order::factory()->for($customer)->create(['number' => 'ORD-10005', 'placed_at' => '2026-10-03 10:00:00']);
        $newest = $this->orderWith('processing', '2026-10-09 10:00:00', [['84.50', 2]], $customer);

        $html = $this->mainContent();
        preg_match_all('#>(ORD-\d+)</a></td>#', $html, $numbers);

        // Same date: the later-created order (higher id) comes first.
        $this->assertSame([$newest->number, 'ORD-10004', 'ORD-10003', 'ORD-10005', 'ORD-10002'], $numbers[1]);
        $this->assertStringContainsString('<td class="is-numeric">€169.00</td>', $html);
        $this->assertStringContainsString('href="'.route('customers.show', $customer).'">Clara Fontaine</a>', $html);
        $this->assertStringContainsString('View<span class="visually-hidden"> order '.$newest->number.'</span>', $html);
        $this->assertStringContainsString('View all orders', $html);
    }

    public function test_best_sellers_rank_delivered_units_by_sku_and_keep_deleted_products(): void
    {
        $product = Product::factory()->create(['name' => 'Rose Nocturne', 'sku' => 'AN-FLR-001']);

        $delivered = Order::factory()->create(['status' => 'delivered']);
        OrderItem::factory()->for($delivered)->for($product)->create(['product_name' => 'Rose Nocturne', 'product_sku' => 'AN-FLR-001', 'quantity' => 2]);
        OrderItem::factory()->for($delivered)->create(['product_name' => 'Brise Marine', 'product_sku' => 'AN-FRS-001', 'quantity' => 3]);
        // Named so that name order and SKU order disagree for this tie.
        OrderItem::factory()->for($delivered)->create(['product_name' => 'Zeste Ambré', 'product_sku' => 'AN-AMB-001', 'quantity' => 3]);

        foreach (['AN-GRM-013', 'AN-GRM-011', 'AN-GRM-012', 'AN-GRM-010'] as $sku) {
            OrderItem::factory()->for($delivered)->create(['product_name' => 'Sample '.$sku, 'product_sku' => $sku, 'quantity' => 1]);
        }

        $alsoDelivered = Order::factory()->create(['status' => 'delivered']);
        OrderItem::factory()->for($alsoDelivered)->for($product)->create(['product_name' => 'Rose Nocturne', 'product_sku' => 'AN-FLR-001', 'quantity' => 3]);

        $cancelled = Order::factory()->create(['status' => 'cancelled']);
        OrderItem::factory()->for($cancelled)->create(['product_name' => 'Cacao Velouté', 'product_sku' => 'AN-GRM-003', 'quantity' => 20]);
        $pending = Order::factory()->create(['status' => 'pending']);
        OrderItem::factory()->for($pending)->create(['product_name' => 'Vanille Noire', 'product_sku' => 'AN-GRM-001', 'quantity' => 20]);

        $product->delete();

        preg_match('#<ol class="best-sellers".*?</ol>#s', $this->mainContent(), $list);
        $text = preg_replace('/\s+/', ' ', trim(strip_tags($list[0])));

        // 5 units for the deleted product, then ties broken by SKU; only the top five are listed.
        $this->assertSame(5, substr_count($list[0], '<li class="best-sellers__item">'));
        $this->assertSame(
            '1 Rose Nocturne AN-FLR-001 5 units 2 Zeste Ambré AN-AMB-001 3 units 3 Brise Marine AN-FRS-001 3 units '
            .'4 Sample AN-GRM-010 AN-GRM-010 1 unit 5 Sample AN-GRM-011 AN-GRM-011 1 unit',
            $text
        );
    }

    public function test_best_sellers_show_the_name_from_the_latest_delivered_order(): void
    {
        // A renamed product: the newest delivered name wins, although it sorts first alphabetically.
        $this->deliveredItem('AN-FLR-001', 'Rose Nocturne', '2026-09-01 10:00:00');
        $this->deliveredItem('AN-FLR-001', 'Ancienne Rose', '2026-10-01 10:00:00');
        $this->deliveredItem('AN-FLR-001', 'Rose Classique', '2026-08-01 10:00:00');
        $later = Order::factory()->create(['status' => 'cancelled', 'placed_at' => '2026-10-15 10:00:00']);
        OrderItem::factory()->for($later)->create(['product_name' => 'Rose Cancelled', 'product_sku' => 'AN-FLR-001', 'quantity' => 1]);

        // Two delivered orders placed at the same time: the later-created order wins.
        $this->deliveredItem('AN-WDY-001', 'Zeste Boisé', '2026-10-02 10:00:00');
        $this->deliveredItem('AN-WDY-001', 'Bois Clair', '2026-10-02 10:00:00');

        preg_match('#<ol class="best-sellers".*?</ol>#s', $this->mainContent(), $list);
        $text = preg_replace('/\s+/', ' ', trim(strip_tags($list[0])));

        $this->assertSame('1 Ancienne Rose AN-FLR-001 3 units 2 Bois Clair AN-WDY-001 2 units', $text);
    }

    public function test_each_chart_has_a_visible_fallback_for_when_it_cannot_be_drawn(): void
    {
        $this->orderWith('delivered', now()->toDateString(), [['84.50', 1]]);

        $html = $this->mainContent();

        $this->assertSame(2, substr_count($html, '<div class="chart-figure">'));
        $this->assertSame(2, substr_count($html, '<p class="chart-figure__fallback">The chart cannot be shown, so its figures are listed below.</p>'));
        $this->assertSame(2, substr_count($html, '<div class="chart-figure__data">'));
        $this->assertStringNotContainsString('is-unavailable', $html);
    }

    public function test_catalog_and_customer_figures_count_every_record(): void
    {
        Product::factory()->count(2)->create();
        Product::factory()->hidden()->create();
        Customer::factory()->count(4)->create();

        $html = $this->mainContent();

        $this->assertMatchesRegularExpression('#Products in Catalog</p>\s*<p class="stat-card__value">3</p>\s*<p class="stat-card__hint">2 active</p>#', $html);
        $this->assertMatchesRegularExpression('#Total Customers</p>\s*<p class="stat-card__value">4</p>#', $html);
    }

    public function test_the_number_of_queries_does_not_grow_with_the_orders(): void
    {
        $this->orderWith('delivered', '2026-10-05', [['10.00', 1]]);
        $few = $this->countQueries();

        foreach (range(1, 8) as $i) {
            $this->orderWith($i % 2 ? 'delivered' : 'pending', '2026-10-05', [['10.00', 1], ['5.00', 2]]);
        }

        $this->assertSame($few, $this->countQueries());
    }

    public function test_a_filled_dashboard_has_unique_ids_and_valid_references(): void
    {
        $this->orderWith('delivered', now()->toDateString(), [['84.50', 1]]);

        $html = $this->dashboard()->getContent();
        preg_match_all('#\sid="([^"]+)"#', $html, $ids);
        preg_match_all('#aria-(?:labelledby|controls)="([^"]+)"#', $html, $references);

        $this->assertSame([], array_keys(array_filter(array_count_values($ids[1]), fn (int $count) => $count > 1)));
        $this->assertSame([], array_values(array_diff($references[1], $ids[1])));
        $this->assertSame(2, substr_count($html, '<canvas role="img" aria-label="'));
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

    /**
     * @param  list<array{string, int}>  $items  [unit price, quantity]
     */
    private function orderWith(string $status, string $placedAt, array $items, ?Customer $customer = null): Order
    {
        $order = Order::factory()->for($customer ?? Customer::factory())->create(['status' => $status, 'placed_at' => $placedAt]);

        foreach ($items as [$price, $quantity]) {
            OrderItem::factory()->for($order)->create(['unit_price' => $price, 'quantity' => $quantity]);
        }

        return $order;
    }

    private function deliveredItem(string $sku, string $name, string $placedAt): void
    {
        $order = Order::factory()->create(['status' => 'delivered', 'placed_at' => $placedAt]);
        OrderItem::factory()->for($order)->create(['product_name' => $name, 'product_sku' => $sku, 'quantity' => 1]);
    }

    /**
     * The decoded data-chart attribute of the chart with the given format.
     *
     * @return array<string, mixed>
     */
    private function chart(string $format): array
    {
        preg_match_all('#data-chart="([^"]+)"#', $this->dashboard()->getContent(), $matches);
        $charts = array_map(fn (string $json) => json_decode(html_entity_decode($json), true), $matches[1]);

        return collect($charts)->firstOrFail(fn (array $chart) => $chart['format'] === $format);
    }

    private function countQueries(): int
    {
        $user = User::factory()->create();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
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
