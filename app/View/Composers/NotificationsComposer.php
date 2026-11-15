<?php

namespace App\View\Composers;

use App\Models\Customer;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Notifications for the top bar bell, read from the orders, customers and
 * messages tables themselves, so they also cover records written by other
 * systems or inserted by hand.
 *
 * A notification is unread when its record was created after the user's
 * notifications_read_at (or their account's creation date if never set) and
 * the user has not opened it from the bell (a notification_reads row).
 */
class NotificationsComposer
{
    /**
     * Notifications shown in the dropdown.
     */
    private const LIMIT = 8;

    /**
     * Each notification type: its model, the section a role needs to see it,
     * and the page it opens.
     */
    public const SOURCES = [
        'order' => ['model' => Order::class, 'section' => 'orders', 'route' => 'orders.show'],
        'customer' => ['model' => Customer::class, 'section' => 'customers', 'route' => 'customers.show'],
        'message' => ['model' => Message::class, 'section' => 'messages', 'route' => 'messages.show'],
    ];

    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user) {
            $view->with(['notifications' => collect(), 'unreadCount' => 0]);

            return;
        }

        $view->with([
            'notifications' => $this->latest($user),
            'unreadCount' => $this->unreadCount($user),
        ]);
    }

    /**
     * Unread notifications from the sources the user's role may open
     * (Sellers see no new customers). Also used after opening one.
     */
    public function unreadCount(User $user): int
    {
        $since = $this->since($user);
        $count = 0;

        foreach (self::SOURCES as $type => $source) {
            if ($user->can('access-'.$source['section'])) {
                $count += $this->unread($source['model']::query(), $user, $type, $since)->count();
            }
        }

        return $count;
    }

    /**
     * Records created after the threshold that the user has not opened from the bell.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    private function unread(Builder $query, User $user, string $type, \DateTimeInterface $since): Builder
    {
        $table = $query->getModel()->getTable();

        return $query->where("{$table}.created_at", '>', $since)
            ->whereNotExists(fn ($reads) => $reads->from('notification_reads')
                ->where('notification_reads.user_id', $user->id)
                ->where('notification_reads.type', $type)
                ->whereColumn('notification_reads.record_id', "{$table}.id"));
    }

    private function since(User $user): \DateTimeInterface
    {
        return $user->notifications_read_at ?? $user->created_at ?? now();
    }

    /**
     * The newest records of each kind, merged and cut to the newest eight.
     * Records without a creation date cannot be placed in time and are left out.
     *
     * @return Collection<int, array{type: string, id: int, icon: string, message: string, created_at: \Illuminate\Support\Carbon, url: string, read_url: string, unread: bool}>
     */
    private function latest(User $user): Collection
    {
        $orders = ! $user->can('access-orders') ? collect() : Order::query()
            ->with('customer:id,name')
            ->whereNotNull('created_at')
            ->latest()
            ->latest('id')
            ->limit(self::LIMIT)
            ->get(['id', 'number', 'customer_id', 'created_at'])
            ->map(fn (Order $order) => [
                'type' => 'order',
                'id' => $order->id,
                'icon' => 'shopping-bag',
                'message' => "New order {$order->number} from {$order->customer->name}",
                'created_at' => $order->created_at,
            ]);

        $customers = ! $user->can('access-customers') ? collect() : Customer::query()
            ->whereNotNull('created_at')
            ->latest()
            ->latest('id')
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'created_at'])
            ->map(fn (Customer $customer) => [
                'type' => 'customer',
                'id' => $customer->id,
                'icon' => 'users',
                'message' => "New customer {$customer->name}",
                'created_at' => $customer->created_at,
            ]);

        $messages = ! $user->can('access-messages') ? collect() : Message::query()
            ->whereNotNull('created_at')
            ->latest()
            ->latest('id')
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'subject', 'created_at'])
            ->map(fn (Message $message) => [
                'type' => 'message',
                'id' => $message->id,
                'icon' => 'mail',
                'message' => "New message from {$message->name}: {$message->subject}",
                'created_at' => $message->created_at,
            ]);

        $latest = $orders->toBase()
            ->merge($customers)
            ->merge($messages)
            ->sortByDesc(fn (array $notification) => $notification['created_at']->getTimestamp())
            ->take(self::LIMIT)
            ->values();

        // Which of these the user already opened from the bell, in one query.
        $opened = $latest->isEmpty() ? collect() : DB::table('notification_reads')
            ->where('user_id', $user->id)
            ->whereIn('record_id', $latest->pluck('id')->unique()->all())
            ->get(['type', 'record_id'])
            ->map(fn (object $read) => "{$read->type}:{$read->record_id}");

        $since = $this->since($user);

        return $latest->map(fn (array $notification) => [
            ...$notification,
            'url' => route(self::SOURCES[$notification['type']]['route'], $notification['id']),
            'read_url' => route('notifications.read', ['type' => $notification['type'], 'id' => $notification['id']]),
            'unread' => $notification['created_at']->gt($since)
                && ! $opened->contains("{$notification['type']}:{$notification['id']}"),
        ]);
    }
}
