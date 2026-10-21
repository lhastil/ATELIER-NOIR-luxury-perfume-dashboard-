<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\SampleOrderSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_reach_order_pages_or_change_a_status(): void
    {
        $order = Order::factory()->create();

        $this->get(route('orders.index'))->assertRedirect(route('login'));
        $this->get(route('orders.show', $order))->assertRedirect(route('login'));
        $this->patch(route('orders.status.update', $order), ['status' => 'shipped'])->assertRedirect(route('login'));

        $this->assertSame('pending', $order->refresh()->status);
    }

    public function test_search_and_status_filter_narrow_the_list(): void
    {
        $amelie = Customer::factory()->create(['name' => 'Amélie Laurent', 'email' => 'amelie.laurent@example.test']);
        $julien = Customer::factory()->create(['name' => 'Julien Moreau', 'email' => 'julien.moreau@example.test']);
        Order::factory()->for($amelie)->create(['number' => 'ORD-10001', 'status' => 'delivered']);
        Order::factory()->for($julien)->create(['number' => 'ORD-10002', 'status' => 'pending']);

        $this->signIn();

        $this->get(route('orders.index', ['search' => '10002']))->assertSee('ORD-10002')->assertDontSee('ORD-10001');
        $this->get(route('orders.index', ['search' => 'Laurent']))->assertSee('ORD-10001')->assertDontSee('ORD-10002');
        $this->get(route('orders.index', ['search' => 'julien.moreau@']))->assertSee('ORD-10002')->assertDontSee('ORD-10001');
        $this->get(route('orders.index', ['status' => 'delivered']))->assertSee('ORD-10001')->assertDontSee('ORD-10002');
        $this->get(route('orders.index', ['search' => 'Laurent', 'status' => 'pending']))->assertSee('No matching orders');
        $this->get(route('orders.index', ['search' => '10001', 'status' => 'pending']))->assertSee('No matching orders');
        $this->get('/orders?search[]=x&status[]=pending')->assertOk()->assertSee('ORD-10001')->assertSee('ORD-10002');
    }

    public function test_the_list_is_newest_first_paginated_and_keeps_the_filters(): void
    {
        Order::factory()->create(['number' => 'ORD-10050', 'status' => 'shipped', 'placed_at' => now()->subDays(9)]);
        Order::factory()->count(10)->create(['status' => 'shipped', 'placed_at' => now()->subDays(5)]);
        Order::factory()->create(['number' => 'ORD-10099', 'status' => 'shipped', 'placed_at' => now()]);
        Order::factory()->create(['number' => 'ORD-10100', 'status' => 'pending']);

        $this->signIn()->get(route('orders.index', ['status' => 'shipped']))
            ->assertOk()
            ->assertSee('12 orders match the filters')
            ->assertSee('ORD-10099')
            ->assertDontSee('ORD-10050')
            ->assertSee('status=shipped&amp;page=2', false);

        $this->get(route('orders.index', ['status' => 'shipped', 'page' => 2]))
            ->assertSee('ORD-10050')
            ->assertDontSee('ORD-10099')
            ->assertDontSee('ORD-10100');
    }

    public function test_the_details_page_shows_items_totals_and_the_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Clara Fontaine', 'email' => 'clara.fontaine@example.test', 'phone' => '+33639980003']);
        $order = Order::factory()->for($customer)->create(['number' => 'ORD-10001']);
        $product = Product::factory()->create();
        OrderItem::factory()->for($order)->for($product)->create(['product_name' => 'Jardin d’Iris', 'unit_price' => '84.50', 'quantity' => 2]);
        OrderItem::factory()->for($order)->create(['product_name' => 'Brise Marine', 'product_sku' => 'AN-FRS-001', 'unit_price' => '78.00', 'quantity' => 1]);

        $this->signIn()->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Order ORD-10001')
            ->assertSeeInOrder(['Jardin d’Iris', '<td class="is-numeric">2</td>', '<td class="is-numeric">€84.50</td>', '<td class="is-numeric">€169.00</td>'], false)
            ->assertSee('href="'.route('products.edit', $product).'"', false)
            ->assertSee('AN-FRS-001 · No longer in the catalog')
            ->assertSee('€247.00')
            ->assertSee('Clara Fontaine')
            ->assertSee('clara.fontaine@example.test')
            ->assertSee('<a href="tel:+33639980003">06 39 98 00 03</a>', false);
    }

    public function test_an_admin_can_change_the_status(): void
    {
        $order = Order::factory()->create(['number' => 'ORD-10001']);

        $this->signIn()->patch(route('orders.status.update', $order), ['status' => 'shipped'])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('status', 'Order ORD-10001 is now marked as shipped.');

        $this->assertSame('shipped', $order->refresh()->status);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $order = Order::factory()->create();

        $this->signIn()->from(route('orders.show', $order))
            ->patch(route('orders.status.update', $order), ['status' => 'refunded'])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors('status');

        $this->assertSame('pending', $order->refresh()->status);
    }

    public function test_the_total_adds_up_every_item_with_decimal_prices(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->create(['unit_price' => '84.50', 'quantity' => 2]);
        OrderItem::factory()->for($order)->create(['unit_price' => '0.10', 'quantity' => 3]);
        OrderItem::factory()->for($order)->create(['unit_price' => '128.00', 'quantity' => 1]);

        $order->refresh();

        $this->assertSame(29730, $order->total_cents);
        $this->assertSame('€297.30', $order->formatted_total);
        $this->assertSame('€169.00', $order->items->first()->formatted_line_total);
    }

    public function test_orders_link_customers_items_and_products(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->for($customer)->create();
        $item = OrderItem::factory()->for($order)->for($product)->create();

        $this->assertTrue($customer->orders->first()->is($order));
        $this->assertTrue($order->customer->is($customer));
        $this->assertTrue($order->items->first()->is($item));
        $this->assertTrue($item->order->is($order));
        $this->assertTrue($item->product->is($product));
    }

    public function test_order_items_keep_their_details_when_the_product_changes_or_is_deleted(): void
    {
        $product = Product::factory()->create(['name' => 'Rose Nocturne', 'sku' => 'AN-FLR-001', 'price' => '128.00']);
        $item = OrderItem::factory()->for($product)->create([
            'product_name' => 'Rose Nocturne', 'product_sku' => 'AN-FLR-001', 'unit_price' => '128.00', 'quantity' => 2,
        ]);

        $product->update(['name' => 'Rose Nocturne Intense', 'price' => '149.00']);
        $this->assertSame('Rose Nocturne', $item->refresh()->product_name);
        $this->assertSame('€256.00', $item->order->formatted_total);

        $this->signIn()
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $item->refresh();
        $this->assertNull($item->product_id);
        $this->assertSame('Rose Nocturne', $item->product_name);
        $this->assertSame('AN-FLR-001', $item->product_sku);
        $this->assertSame('128.00', $item->unit_price);
        $this->assertSame('€256.00', $item->order->formatted_total);
    }

    public function test_a_customer_with_orders_cannot_be_deleted(): void
    {
        $order = Order::factory()->create();

        $this->expectException(QueryException::class);

        $order->customer->delete();
    }

    public function test_deleting_an_order_deletes_its_items(): void
    {
        $item = OrderItem::factory()->create();

        $item->order->delete();

        $this->assertModelMissing($item);
    }

    public function test_sample_orders_seed_once_and_keep_status_changes(): void
    {
        $this->seed(SampleOrderSeeder::class);
        $itemCount = OrderItem::count();
        Order::where('number', 'ORD-10040')->update(['status' => 'cancelled']);

        $this->seed(SampleOrderSeeder::class);

        $this->assertSame(24, Customer::count());
        $this->assertSame(40, Order::count());
        $this->assertSame(15, Product::count());
        $this->assertSame($itemCount, OrderItem::count());
        $this->assertSame('cancelled', Order::where('number', 'ORD-10040')->value('status'));
        $this->assertSame(0, Order::doesntHave('items')->count());
        $this->assertSame(3, Customer::doesntHave('orders')->count());
        $this->assertEqualsCanonicalizing(Order::STATUSES, Order::distinct()->pluck('status')->all());
    }

    public function test_seeding_in_production_creates_only_the_categories(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertSame(5, Category::count());
        $this->assertSame(0, User::count());
        $this->assertSame(0, Product::count());
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Order::count());
    }

    private function signIn(): static
    {
        return $this->actingAs(User::factory()->create());
    }
}
