<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_reach_customer_pages(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('customers.index'))->assertRedirect(route('login'));
        $this->get(route('customers.show', $customer))->assertRedirect(route('login'));
    }

    public function test_search_finds_customers_by_name_email_or_phone(): void
    {
        Customer::factory()->create(['name' => 'Amélie Laurent', 'email' => 'amelie.laurent@example.test', 'phone' => '+33639980001']);
        $julien = Customer::factory()->create(['name' => 'Julien Moreau', 'email' => 'julien.moreau@example.test', 'phone' => '+33639980002']);
        Order::factory()->for($julien)->create(['placed_at' => '2026-07-13 10:00:00']);
        Order::factory()->for($julien)->create(['placed_at' => '2026-09-14 10:00:00']);

        $this->signIn();

        $this->get(route('customers.index'))
            ->assertSeeInOrder(['Amélie Laurent', '<td class="is-numeric">0</td>', 'Julien Moreau', '<td class="is-numeric">2</td>', '14 Sep 2026'], false)
            ->assertDontSee('13 Jul 2026');

        $this->get(route('customers.index', ['search' => 'Laurent']))->assertSee('Amélie Laurent')->assertDontSee('Julien Moreau');
        $this->get(route('customers.index', ['search' => 'julien.moreau@']))->assertSee('Julien Moreau')->assertDontSee('Amélie Laurent');

        foreach (['06 39 98 00 02', '0639980002', '+33 6 39 98 00 02', '+33639980002', '39 98 00 02', '0002'] as $phone) {
            $this->get(route('customers.index', ['search' => $phone]))->assertSee('1 customer matches the search')->assertSee('Julien Moreau')->assertDontSee('Amélie Laurent');
        }

        $this->get(route('customers.index', ['search' => '06 39 98']))->assertSee('Julien Moreau')->assertSee('Amélie Laurent');
        $this->get(route('customers.index', ['search' => '07 39 98']))->assertSee('No matching customers');
        $this->get(route('customers.index', ['search' => 'nobody']))->assertSee('No matching customers');
        $this->get('/customers?search[]=x')->assertOk()->assertSee('Amélie Laurent')->assertSee('Julien Moreau');
    }

    public function test_the_list_is_paginated_and_keeps_the_search(): void
    {
        Customer::factory()->count(12)->create(['name' => 'Camille Girard']);
        Customer::factory()->create(['name' => 'Hugo Lefèvre']);

        $this->signIn()->get(route('customers.index', ['search' => 'Girard']))
            ->assertOk()
            ->assertSee('12 customers match the search')
            ->assertSee('search=Girard&amp;page=2', false);
    }

    public function test_the_details_page_shows_contact_details_and_only_this_customers_orders(): void
    {
        $customer = Customer::factory()->create(['email' => 'clara.fontaine@example.test', 'phone' => '+33639980003']);
        $order = Order::factory()->for($customer)->create(['number' => 'ORD-10001', 'status' => 'delivered']);
        OrderItem::factory()->for($order)->create(['unit_price' => '96.00', 'quantity' => 2]);
        Order::factory()->create(['number' => 'ORD-10002']);

        $this->signIn()->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('clara.fontaine@example.test')
            ->assertSee('<a href="tel:+33639980003">06 39 98 00 03</a>', false)
            ->assertDontSee('+33639980003</a>', false)
            ->assertSee('ORD-10001')
            ->assertSee('€192.00')
            ->assertSee('Delivered')
            ->assertDontSee('ORD-10002');
    }

    public function test_only_french_numbers_are_reformatted_for_display(): void
    {
        $this->assertSame('06 39 98 00 01', Customer::factory()->make(['phone' => '+33639980001'])->formatted_phone);
        $this->assertSame('+44 20 7946 0000', Customer::factory()->make(['phone' => '+44 20 7946 0000'])->formatted_phone);
        $this->assertNull(Customer::factory()->make(['phone' => null])->formatted_phone);
    }

    public function test_a_customer_without_orders_shows_an_empty_history(): void
    {
        $customer = Customer::factory()->create();

        $this->signIn()->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('No orders yet');
    }

    private function signIn(): static
    {
        return $this->actingAs(User::factory()->create());
    }
}
