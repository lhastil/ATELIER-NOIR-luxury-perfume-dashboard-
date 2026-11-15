<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_reach_or_change_messages(): void
    {
        $message = $this->message();

        $this->get(route('messages.index'))->assertRedirect(route('login'));
        $this->get(route('messages.show', $message))->assertRedirect(route('login'));
        $this->patch(route('messages.status.update', $message), ['status' => 'read'])->assertRedirect(route('login'));
        $this->delete(route('messages.destroy', $message))->assertRedirect(route('login'));

        $message->refresh();
        $this->assertNull($message->read_at);
        $this->assertModelExists($message);
    }

    public function test_the_inbox_lists_messages_newest_first_and_highlights_unread_ones(): void
    {
        $this->message(['subject' => 'Older question', 'created_at' => now()->subDays(2), 'read_at' => now()]);
        $this->message(['subject' => 'Newest question', 'created_at' => now()]);

        $html = $this->signIn()->get(route('messages.index'))
            ->assertOk()
            ->assertSeeInOrder(['Newest question', 'Older question'])
            ->getContent();

        $this->assertSame(1, substr_count($html, 'class="message-row--unread"'));
        $this->assertSame(2, Message::count());
    }

    public function test_the_inbox_is_paginated_and_keeps_the_filters(): void
    {
        foreach (range(1, 11) as $day) {
            $this->message(['subject' => "Question {$day}", 'created_at' => now()->subDays($day)]);
        }

        $this->signIn()->get(route('messages.index', ['status' => 'unread']))
            ->assertSee('Question 1')
            ->assertDontSee('Question 11')
            ->assertSee('status=unread&amp;page=2', false);

        $this->get(route('messages.index', ['status' => 'unread', 'page' => 2]))->assertSee('Question 11');
    }

    public function test_search_finds_messages_by_name_email_or_subject(): void
    {
        $this->message(['name' => 'Amelie Laurent', 'email' => 'amelie@example.test', 'subject' => 'Gift wrapping']);
        $this->message(['name' => 'Julien Moreau', 'email' => 'julien@example.test', 'subject' => 'Delivery delay']);

        $this->signIn();

        $this->get(route('messages.index', ['search' => 'Laurent']))->mainContent()->assertSee('Gift wrapping')->assertDontSee('Delivery delay');
        $this->get(route('messages.index', ['search' => 'julien@']))->mainContent()->assertSee('Delivery delay')->assertDontSee('Gift wrapping');
        $this->get(route('messages.index', ['search' => 'wrapping']))->mainContent()->assertSee('Gift wrapping')->assertDontSee('Delivery delay');
        $this->get(route('messages.index', ['search' => 'nothing like this']))->mainContent()->assertSee('No matching messages');
    }

    public function test_the_status_filter_shows_read_or_unread_messages(): void
    {
        $this->message(['subject' => 'Already read', 'read_at' => now()]);
        $this->message(['subject' => 'Still unread']);

        $this->signIn();

        $this->get(route('messages.index', ['status' => 'unread']))->mainContent()->assertSee('Still unread')->assertDontSee('Already read');
        $this->get(route('messages.index', ['status' => 'read']))->mainContent()->assertSee('Already read')->assertDontSee('Still unread');
        $this->get(route('messages.index', ['status' => 'other']))->mainContent()->assertSee('Already read')->assertSee('Still unread');
    }

    public function test_opening_an_unread_message_shows_it_and_marks_it_as_read(): void
    {
        $customer = Customer::factory()->create();
        $message = $this->message([
            'customer_id' => $customer->id,
            'name' => 'Clara Fontaine',
            'email' => 'clara@example.test',
            'subject' => 'Engraving',
            'body' => "Hello,\nCan the bottle be engraved? <b>Thanks</b>",
        ]);

        $html = $this->signIn()->get(route('messages.show', $message))
            ->assertOk()
            ->assertSee('Engraving')
            ->assertSee('Clara Fontaine')
            ->assertSee('clara@example.test')
            ->assertSee("Hello,\nCan the bottle be engraved? &lt;b&gt;Thanks&lt;/b&gt;", false)
            ->assertSee('href="'.route('customers.show', $customer).'"', false)
            ->assertSee('Mark as unread')
            ->getContent();

        $this->assertNotNull($message->refresh()->read_at);

        // The sidebar's Messages entry is a link and stays current on a message's page.
        $this->assertMatchesRegularExpression('#<a href="'.preg_quote(route('messages.index'), '#').'" class="nav-item"\s+aria-current="page"#', $html);
    }

    public function test_opening_a_read_message_keeps_its_read_date(): void
    {
        $readAt = now()->subDay()->startOfSecond();
        $message = $this->message(['read_at' => $readAt]);

        $this->signIn()->get(route('messages.show', $message))->assertOk();

        $this->assertTrue($readAt->equalTo($message->refresh()->read_at));
    }

    public function test_a_message_can_be_marked_as_unread_and_read_again(): void
    {
        $message = $this->message(['subject' => 'Engraving', 'read_at' => now()]);

        $this->signIn()->patch(route('messages.status.update', $message), ['status' => 'unread'])
            ->assertRedirect(route('messages.index'))
            ->assertSessionHas('status', 'Message "Engraving" was marked as unread.');
        $this->assertNull($message->refresh()->read_at);

        $this->patch(route('messages.status.update', $message), ['status' => 'read'])
            ->assertRedirect(route('messages.show', $message));
        $this->assertNotNull($message->refresh()->read_at);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $message = $this->message(['read_at' => now()]);

        $this->signIn()->from(route('messages.show', $message))
            ->patch(route('messages.status.update', $message), ['status' => 'archived'])
            ->assertRedirect(route('messages.show', $message))
            ->assertSessionHasErrors('status');

        $this->assertNotNull($message->refresh()->read_at);
    }

    public function test_a_message_can_be_deleted(): void
    {
        $message = $this->message(['subject' => 'Engraving']);
        $other = $this->message();

        $this->signIn()->get(route('messages.index'))
            ->assertSee('data-confirm="Delete the message &quot;Engraving&quot;? This cannot be undone."', false);

        $this->delete(route('messages.destroy', $message))
            ->assertRedirect(route('messages.index'))
            ->assertSessionHas('status', 'Message "Engraving" was deleted.');

        $this->assertModelMissing($message);
        $this->assertModelExists($other);
    }

    public function test_unknown_messages_return_not_found(): void
    {
        $this->signIn();

        $this->get(route('messages.show', 999999))->assertNotFound();
        $this->patch(route('messages.status.update', 999999), ['status' => 'read'])->assertNotFound();
        $this->delete(route('messages.destroy', 999999))->assertNotFound();
    }

    public function test_deleting_a_customer_keeps_their_messages(): void
    {
        $customer = Customer::factory()->create();
        $message = $this->message(['customer_id' => $customer->id]);

        $customer->delete();

        $this->assertNull($message->refresh()->customer_id);
    }

    /**
     * Messages are written by other systems, so there is no factory; read_at and
     * created_at are set directly here.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function message(array $attributes = []): Message
    {
        return Message::forceCreate(array_merge([
            'name' => 'Sender Name',
            'email' => 'sender@example.test',
            'subject' => 'A question',
            'body' => 'Message text.',
        ], $attributes));
    }

    private function signIn(): static
    {
        return $this->actingAs(User::factory()->create());
    }
}
