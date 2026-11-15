<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_search(): void
    {
        $this->get(route('search', ['q' => 'rose']))->assertRedirect(route('login'));
        $this->get(route('search', ['q' => 'rose']), ['X-Requested-With' => 'XMLHttpRequest'])->assertRedirect(route('login'));
    }

    public function test_products_are_found_by_name_or_sku_but_not_brand(): void
    {
        $byName = Product::factory()->create(['name' => 'Rose Nocturne', 'sku' => 'AN-FLR-001', 'brand' => 'Maison A']);
        $bySku = Product::factory()->create(['name' => 'Cedre Ombre', 'sku' => 'AN-ROS-002', 'brand' => 'Maison B']);
        Product::factory()->create(['name' => 'Ambre Royal', 'sku' => 'AN-AMB-003', 'brand' => 'Rose House']);

        $this->search('ros')
            ->assertSee('href="'.route('products.edit', $byName).'"', false)
            ->assertSee('href="'.route('products.edit', $bySku).'"', false)
            ->assertSee('AN-FLR-001')
            ->assertDontSee('Ambre Royal');
    }

    public function test_categories_are_found_by_name(): void
    {
        $woody = Category::factory()->create(['name' => 'Woody']);
        Category::factory()->create(['name' => 'Floral']);
        Product::factory()->count(2)->for($woody)->create(['name' => 'Unrelated']);

        $this->search('woo')
            ->assertSee('href="'.route('categories.edit', $woody).'"', false)
            ->assertSee('2 products')
            ->assertDontSee('Floral');
    }

    public function test_customers_are_found_by_name_email_or_phone(): void
    {
        $clara = Customer::factory()->create(['name' => 'Clara Fontaine', 'email' => 'clara@example.test', 'phone' => '+33639980003']);
        $julien = Customer::factory()->create(['name' => 'Julien Moreau', 'email' => 'jm@example.test', 'phone' => '+33611112222']);

        $this->search('fontaine')->mainContent()->assertSee('href="'.route('customers.show', $clara).'"', false)->assertSee('clara@example.test')->assertDontSee('Julien Moreau');
        $this->search('jm@example')->mainContent()->assertSee('href="'.route('customers.show', $julien).'"', false)->assertDontSee('Clara Fontaine');
        // The customer list's phone matching: spaces ignored and a leading 0 also tried as +33.
        $this->search('06 39 98')->mainContent()->assertSee('Clara Fontaine')->assertDontSee('Julien Moreau');
        $this->search('+33 6 11')->mainContent()->assertSee('Julien Moreau')->assertDontSee('Clara Fontaine');
    }

    public function test_orders_are_found_by_number_only(): void
    {
        $customer = Customer::factory()->create(['name' => 'Clara Fontaine']);
        $order = Order::factory()->for($customer)->create(['number' => 'ORD-10042', 'placed_at' => '2026-10-05 10:00:00']);
        Order::factory()->create(['number' => 'ORD-20001']);

        $this->search('10042')
            ->mainContent()
            ->assertSee('href="'.route('orders.show', $order).'"', false)
            ->assertSee('Clara Fontaine · 5 Oct 2026')
            ->assertDontSee('ORD-20001');

        // The customer's name does not find their orders here (the Orders page search does).
        $this->search('fontaine')->mainContent()->assertDontSee('ORD-10042');
    }

    public function test_messages_are_found_by_sender_name_email_or_subject(): void
    {
        $message = Message::forceCreate(['name' => 'Amelie Laurent', 'email' => 'amelie@example.test', 'subject' => 'Gift wrapping', 'body' => 'Secret engraving text']);

        foreach (['laurent', 'amelie@', 'wrapping'] as $term) {
            $this->search($term)->mainContent()->assertSee('href="'.route('messages.show', $message).'"', false)->assertSee('Gift wrapping');
        }

        // The message body is not searched.
        $this->search('engraving')->mainContent()->assertDontSee('Gift wrapping');
        // Searching does not open, so does not mark the message as read.
        $this->assertNull($message->refresh()->read_at);
    }

    public function test_short_or_malformed_searches_return_no_results(): void
    {
        Product::factory()->create(['name' => 'Rose Nocturne']);

        $this->search('r')
            ->assertSee('Type at least 2 characters')
            ->assertSee('data-search-count="0"', false)
            ->assertDontSee('Rose Nocturne');

        $this->search('  r  ')->assertSee('Type at least 2 characters');

        $this->actingAs(User::factory()->create())
            ->get('/search?q[]=rose')
            ->assertOk()
            ->assertSee('Type at least 2 characters')
            ->assertDontSee('Rose Nocturne');
    }

    public function test_each_group_shows_at_most_five_results(): void
    {
        foreach (range(1, 7) as $i) {
            Product::factory()->create(['name' => "Rose {$i}", 'sku' => "AN-ROSE-{$i}"]);
        }

        $html = $this->search('rose')->assertSee('data-search-count="5"', false)->getContent();

        $this->assertSame(5, substr_count($html, 'class="search-result"'));
        // Sorted by name, so the first five are shown.
        $this->assertStringContainsString('Rose 5', $html);
        $this->assertStringNotContainsString('Rose 6', $html);
    }

    public function test_no_matches_show_an_empty_state(): void
    {
        $this->search('zzzz')
            ->assertSee('No results')
            ->assertSee('Nothing matches "zzzz".', false)
            ->assertSee('data-search-count="0"', false);
    }

    public function test_search_text_is_escaped(): void
    {
        $this->search('<script>x</script>')
            ->assertDontSee('<script>x</script>', false)
            ->assertSee('&lt;script&gt;x&lt;/script&gt;', false);
    }

    public function test_a_full_page_request_shows_the_results_page_and_keeps_the_search(): void
    {
        Product::factory()->create(['name' => 'Rose Nocturne']);

        $this->search('rose')
            ->assertSee('<title>Search · ATELIER NOIR</title>', false)
            ->assertSee('Results for &quot;rose&quot;', false)
            ->assertSee('Rose Nocturne')
            ->assertSee('name="q" value="rose"', false)
            ->assertHeader('Vary', 'X-Requested-With');
    }

    public function test_a_javascript_request_returns_only_the_results(): void
    {
        Product::factory()->create(['name' => 'Rose Nocturne']);

        $html = $this->actingAs(User::factory()->create())
            ->get(route('search', ['q' => 'rose']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Rose Nocturne')
            ->assertSee('data-search-count="1"', false)
            ->assertHeader('Vary', 'X-Requested-With')
            ->getContent();

        $this->assertStringStartsWith('<div class="search-results"', trim(preg_replace('#\{\{--.*?--\}\}#s', '', $html)));
        $this->assertStringNotContainsString('<html', $html);
        $this->assertStringNotContainsString('topbar', $html);
        $this->assertStringNotContainsString(' id="', $html);
    }

    public function test_the_topbar_search_is_enabled_and_submits_to_the_search_page(): void
    {
        $html = $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->getContent();

        preg_match('#<form class="topbar-search"[^>]*>.*?</form>#s', $html, $form);

        $this->assertStringContainsString('method="GET" action="'.route('search').'"', $form[0]);
        $this->assertMatchesRegularExpression('#<input id="topbar-search"[^>]* name="q" value=""#', $form[0]);
        $this->assertStringNotContainsString('disabled', $form[0]);
        $this->assertStringContainsString('data-global-search-panel', $form[0]);
        $this->assertStringContainsString('role="status"', $form[0]);
    }

    private function search(string $query): TestResponse
    {
        return $this->actingAs(User::factory()->create())->get(route('search', ['q' => $query]))->assertOk();
    }
}
