<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-21 12:00:00');
    }

    public function test_without_records_the_bell_shows_the_empty_state(): void
    {
        $this->page($this->admin())
            ->assertSee('There are no notifications.')
            ->assertSee('aria-label="Notifications"', false)
            ->assertDontSee('notifications-badge')
            ->assertDontSee('Mark all as read');
    }

    public function test_new_orders_customers_and_messages_appear_newest_first(): void
    {
        $julien = $this->customer('Julien Moreau', '2026-10-21 09:00:00');
        $clara = $this->customer('Clara Fontaine', '2026-10-21 10:00:00');
        $order = Order::factory()->for($clara)->create(['number' => 'ORD-10042', 'created_at' => '2026-10-21 11:00:00']);
        $message = $this->message('Amelie Laurent', 'Gift wrapping', '2026-10-21 11:30:00');

        $this->page($this->admin())
            ->assertSeeInOrder([
                'New message from Amelie Laurent: Gift wrapping', '30 minutes ago',
                'New order ORD-10042 from Clara Fontaine', '1 hour ago',
                'New customer Clara Fontaine', '2 hours ago',
                'New customer Julien Moreau', '3 hours ago',
            ])
            ->assertDontSee('There are no notifications.')
            // Each links to its admin page.
            ->assertSee('href="'.route('messages.show', $message).'"', false)
            ->assertSee('href="'.route('orders.show', $order).'"', false)
            ->assertSee('href="'.route('customers.show', $clara).'"', false)
            ->assertSee('href="'.route('customers.show', $julien).'"', false);
    }

    public function test_only_the_newest_eight_are_shown(): void
    {
        foreach (range(1, 10) as $hour) {
            $this->customer("Customer {$hour}", sprintf('2026-10-21 %02d:00:00', $hour));
        }

        $html = $this->page($this->admin())->getContent();

        $this->assertSame(8, substr_count($html, 'class="dropdown__item notification'));
        $this->assertStringContainsString('New customer Customer 10', $html);
        $this->assertStringContainsString('New customer Customer 3', $html);
        $this->assertStringNotContainsString('New customer Customer 2<', $html);
        $this->assertStringNotContainsString('New customer Customer 1<', $html);
    }

    public function test_the_badge_counts_every_unread_record(): void
    {
        $admin = $this->admin('2026-10-01 00:00:00');
        $this->customer('Before', '2026-09-30 12:00:00');
        $customer = $this->customer('After', '2026-10-05 12:00:00');
        Order::factory()->for($customer)->create(['created_at' => '2026-10-06 12:00:00']);
        $this->message('Amelie Laurent', 'Gift wrapping', '2026-10-07 12:00:00');

        $html = $this->page($admin)
            ->assertSee('aria-label="Notifications, 3 unread"', false)
            ->assertSee('<span class="notifications-badge" aria-hidden="true" data-notifications-badge>3</span>', false)
            ->assertSee('Mark all as read')
            ->getContent();

        $this->assertSame(3, substr_count($html, 'class="notification__dot"'));
    }

    public function test_the_badge_shows_nine_plus_above_nine(): void
    {
        foreach (range(1, 12) as $hour) {
            $this->customer("Customer {$hour}", sprintf('2026-10-21 %02d:00:00', $hour - 1));
        }

        $this->page($this->admin('2026-10-01 00:00:00'))
            ->assertSee('aria-label="Notifications, 12 unread"', false)
            ->assertSee('<span class="notifications-badge" aria-hidden="true" data-notifications-badge>9+</span>', false);
    }

    public function test_mark_all_as_read_clears_the_unread_count_and_returns_to_the_page(): void
    {
        $admin = $this->admin('2026-10-01 00:00:00');
        $this->customer('Clara Fontaine', '2026-10-05 12:00:00');

        $this->actingAs($admin)
            ->from(route('customers.index'))
            ->post(route('notifications.read-all'))
            ->assertRedirect(route('customers.index'))
            // No success message: the bell itself shows the result.
            ->assertSessionMissing('status');

        $this->assertTrue(now()->equalTo($admin->refresh()->notifications_read_at));

        // Still listed, but no longer unread.
        $this->page($admin)
            ->assertSee('New customer Clara Fontaine')
            ->assertSee('aria-label="Notifications"', false)
            ->assertDontSee('notifications-badge')
            ->assertDontSee('notification__dot')
            ->assertDontSee('Mark all as read');

        // Anything created afterwards is unread again.
        $this->travel(5)->minutes();
        $this->customer('Julien Moreau', now()->toDateTimeString());

        $this->page($admin)->assertSee('aria-label="Notifications, 1 unread"', false);
    }

    public function test_a_javascript_request_marks_all_as_read_and_gets_json(): void
    {
        $admin = $this->admin('2026-10-01 00:00:00');
        $this->customer('Clara Fontaine', '2026-10-05 12:00:00');

        $this->actingAs($admin)
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertExactJson(['unread_count' => 0])
            ->assertSessionMissing('status');

        $this->assertTrue(now()->equalTo($admin->refresh()->notifications_read_at));

        // The same state the dropdown shows after the script updates it.
        $this->page($admin)
            ->assertSee('New customer Clara Fontaine')
            ->assertDontSee('notifications-badge')
            ->assertDontSee('data-notification-unread', false);
    }

    public function test_a_failed_javascript_request_changes_nothing(): void
    {
        $admin = $this->admin('2026-10-01 00:00:00');
        $this->customer('Clara Fontaine', '2026-10-05 12:00:00');

        // Signed out (for example an expired session): 401, not a redirect, for the script.
        $this->postJson(route('notifications.read-all'))->assertUnauthorized();

        $this->assertNull($admin->refresh()->notifications_read_at);
        $this->page($admin)->assertSee('aria-label="Notifications, 1 unread"', false);
    }

    public function test_the_dropdown_has_the_hooks_the_script_updates(): void
    {
        $this->customer('Clara Fontaine', '2026-10-05 12:00:00');

        $html = $this->page($this->admin('2026-10-01 00:00:00'))->getContent();

        // Blade's component attribute bag writes the bare attribute as data-notifications="data-notifications".
        $this->assertStringContainsString('<div class="dropdown" data-notifications="data-notifications" data-dropdown>', $html);
        $this->assertStringContainsString('<form method="POST" action="'.route('notifications.read-all').'" data-notifications-read-all>', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('data-notifications-badge>1</span>', $html);
        $this->assertSame(2, substr_count($html, 'data-notification-unread'));
        $this->assertStringContainsString('<p class="notifications-error" role="alert" hidden data-notifications-error></p>', $html);
        $this->assertStringContainsString('<p class="visually-hidden" role="status" data-notifications-status></p>', $html);
    }

    public function test_opening_an_unread_notification_marks_only_that_one_as_read(): void
    {
        $admin = $this->admin('2026-10-01 00:00:00');
        $clara = $this->customer('Clara Fontaine', '2026-10-05 12:00:00');
        $julien = $this->customer('Julien Moreau', '2026-10-06 12:00:00');

        $this->page($admin)->assertSee('aria-label="Notifications, 2 unread"', false);

        $this->actingAs($admin)
            ->postJson(route('notifications.read', ['type' => 'customer', 'id' => $clara->id]))
            ->assertOk()
            ->assertExactJson(['unread_count' => 1]);

        $html = $this->page($admin)
            ->assertSee('aria-label="Notifications, 1 unread"', false)
            ->assertSee('data-notifications-badge>1</span>', false)
            ->getContent();

        // Julien's item is still unread (dot, label, hook); Clara's is not.
        $this->assertSame(2, substr_count($html, 'data-notification-unread'));
        $this->assertStringContainsString('data-notification-read="'.route('notifications.read', ['type' => 'customer', 'id' => $julien->id]).'"', $html);
        $this->assertStringNotContainsString('data-notification-read="'.route('notifications.read', ['type' => 'customer', 'id' => $clara->id]).'"', $html);
        $this->assertDatabaseCount('notification_reads', 1);
    }

    public function test_opening_an_already_read_notification_does_not_lower_the_count_again(): void
    {
        $admin = $this->admin('2026-10-10 00:00:00');
        $old = $this->customer('Before Threshold', '2026-10-05 12:00:00');
        $new = $this->customer('After Threshold', '2026-10-15 12:00:00');
        $url = route('notifications.read', ['type' => 'customer', 'id' => $new->id]);

        $this->actingAs($admin)->postJson($url)->assertExactJson(['unread_count' => 0]);
        // Opening it again changes nothing.
        $this->actingAs($admin)->postJson($url)->assertExactJson(['unread_count' => 0]);
        // A notification already read through the threshold also leaves the count alone.
        $this->actingAs($admin)->postJson(route('notifications.read', ['type' => 'customer', 'id' => $old->id]))->assertExactJson(['unread_count' => 0]);

        $this->customer('Later Still', '2026-10-20 12:00:00');
        $this->page($admin)->assertSee('aria-label="Notifications, 1 unread"', false);
    }

    public function test_the_notification_still_opens_its_page(): void
    {
        $admin = $this->admin('2026-10-01 00:00:00');
        $customer = $this->customer('Clara Fontaine', '2026-10-05 12:00:00');
        $order = Order::factory()->for($customer)->create(['created_at' => '2026-10-06 12:00:00']);
        $message = $this->message('Amelie Laurent', 'Gift wrapping', '2026-10-07 12:00:00');

        // Without JavaScript (a plain request), the read address leads to the record's page.
        foreach ([['customer', $customer, 'customers.show'], ['order', $order, 'orders.show'], ['message', $message, 'messages.show']] as [$type, $record, $route]) {
            $this->actingAs($admin)
                ->post(route('notifications.read', ['type' => $type, 'id' => $record->id]))
                ->assertRedirect(route($route, $record));

            $this->actingAs($admin)->get(route($route, $record))->assertOk();
        }

        // The items still link straight to their pages.
        $this->page($admin)
            ->assertSee('href="'.route('orders.show', $order).'"', false)
            ->assertDontSee('notifications-badge');
    }

    public function test_reading_a_notification_follows_the_role_rules(): void
    {
        $seller = User::factory()->seller()->create(['created_at' => '2026-10-01 00:00:00']);
        $customer = $this->customer('Clara Fontaine', '2026-10-05 12:00:00');
        $order = Order::factory()->for($customer)->create(['number' => 'ORD-10042', 'created_at' => '2026-10-06 12:00:00']);

        // Sellers do not see new customers, and cannot mark them read.
        $this->page($seller)
            ->assertSee('New order ORD-10042 from Clara Fontaine')
            ->assertDontSee('New customer Clara Fontaine')
            ->assertSee('aria-label="Notifications, 1 unread"', false);

        $this->actingAs($seller)->postJson(route('notifications.read', ['type' => 'customer', 'id' => $customer->id]))->assertForbidden();
        $this->assertDatabaseCount('notification_reads', 0);

        $this->actingAs($seller)->postJson(route('notifications.read', ['type' => 'order', 'id' => $order->id]))->assertExactJson(['unread_count' => 0]);
    }

    public function test_reading_a_notification_is_per_admin_and_validated(): void
    {
        $first = $this->admin('2026-10-01 00:00:00');
        $second = $this->admin('2026-10-01 00:00:00');
        $customer = $this->customer('Clara Fontaine', '2026-10-05 12:00:00');

        $this->actingAs($first)->postJson(route('notifications.read', ['type' => 'customer', 'id' => $customer->id]))->assertOk();
        $this->page($second)->assertSee('aria-label="Notifications, 1 unread"', false);

        $this->actingAs($first)->postJson('/notifications/product/1/read')->assertNotFound();
        $this->actingAs($first)->postJson(route('notifications.read', ['type' => 'customer', 'id' => 999999]))->assertNotFound();

        auth()->logout();
        $this->postJson(route('notifications.read', ['type' => 'customer', 'id' => $customer->id]))->assertUnauthorized();
    }

    public function test_mark_all_as_read_clears_notifications_opened_one_at_a_time(): void
    {
        $admin = $this->admin('2026-10-01 00:00:00');
        $customer = $this->customer('Clara Fontaine', '2026-10-05 12:00:00');

        $this->actingAs($admin)->postJson(route('notifications.read', ['type' => 'customer', 'id' => $customer->id]));
        $this->assertDatabaseCount('notification_reads', 1);

        $this->actingAs($admin)->postJson(route('notifications.read-all'))->assertExactJson(['unread_count' => 0]);

        $this->assertDatabaseCount('notification_reads', 0);
        $this->page($admin)->assertSee('New customer Clara Fontaine')->assertDontSee('notifications-badge');
    }

    public function test_records_from_the_same_second_are_read_and_later_ones_unread(): void
    {
        $admin = $this->admin('2026-10-01 00:00:00');
        $this->customer('Same Second', '2026-10-21 12:00:00');

        // Marked read at 12:00:00, the second the record was created.
        $this->actingAs($admin)->post(route('notifications.read-all'));

        // Reloaded from the database on the next request: nothing unread, record still listed.
        $this->page($admin)
            ->assertSee('New customer Same Second')
            ->assertDontSee('notifications-badge')
            ->assertDontSee('notification__dot');

        $this->travel(1)->second();
        $this->customer('One Second Later', now()->toDateTimeString());

        $this->page($admin)
            ->assertSee('aria-label="Notifications, 1 unread"', false)
            ->assertSeeInOrder(['New customer One Second Later', '(unread)', 'New customer Same Second']);
    }

    /**
     * MySQL stamps NOW() and CURRENT_TIMESTAMP in the session time zone. It must be
     * UTC like the application, or rows inserted by hand or by other systems read
     * hours ahead and stay unread after "Mark all as read" (the reported bug).
     */
    public function test_the_mysql_session_uses_the_applications_utc_time_zone(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('+00:00', config('database.connections.mysql.timezone'));
    }

    public function test_marking_as_read_only_affects_the_signed_in_admin(): void
    {
        $first = $this->admin('2026-10-01 00:00:00');
        $second = $this->admin('2026-10-01 00:00:00');
        $this->customer('Clara Fontaine', '2026-10-05 12:00:00');

        $this->actingAs($first)->post(route('notifications.read-all'));

        $this->assertNull($second->refresh()->notifications_read_at);
        $this->page($first)->assertDontSee('notifications-badge');
        $this->page($second)->assertSee('aria-label="Notifications, 1 unread"', false);
    }

    public function test_guests_cannot_mark_notifications_as_read(): void
    {
        $admin = $this->admin();

        $this->post(route('notifications.read-all'))->assertRedirect(route('login'));

        $this->assertNull($admin->refresh()->notifications_read_at);
    }

    public function test_a_new_admin_does_not_see_older_records_as_unread(): void
    {
        $this->customer('Clara Fontaine', '2026-10-20 12:00:00');
        $admin = $this->admin('2026-10-21 08:00:00');

        $this->page($admin)
            ->assertSee('New customer Clara Fontaine')
            ->assertSee('aria-label="Notifications"', false)
            ->assertDontSee('notifications-badge')
            ->assertDontSee('notification__dot');
    }

    public function test_records_without_a_creation_date_are_left_out(): void
    {
        // A row inserted by hand without timestamps cannot be placed in time.
        DB::table('messages')->insert(['name' => 'No Date', 'email' => 'nodate@example.test', 'subject' => 'Undated', 'body' => 'Text']);

        $this->page($this->admin('2026-10-01 00:00:00'))
            ->assertDontSee('No Date')
            ->assertSee('There are no notifications.');
    }

    private function admin(string $createdAt = '2026-10-21 11:59:00'): User
    {
        return User::factory()->create(['created_at' => $createdAt]);
    }

    private function customer(string $name, string $createdAt): Customer
    {
        return Customer::factory()->create(['name' => $name, 'created_at' => $createdAt]);
    }

    private function message(string $name, string $subject, string $createdAt): Message
    {
        return Message::forceCreate(['name' => $name, 'email' => 'sender@example.test', 'subject' => $subject, 'body' => 'Text', 'created_at' => $createdAt]);
    }

    /**
     * Any admin page shows the bell; the profile page has no other order or customer lists.
     */
    private function page(User $admin): TestResponse
    {
        return $this->actingAs($admin)->get(route('profile.edit'))->assertOk();
    }
}
