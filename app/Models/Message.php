<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message sent to the house. The read status (read_at) is not fillable:
 * only the admin panel changes it (see MessageController).
 */
#[Fillable(['customer_id', 'name', 'email', 'subject', 'body'])]
class Message extends Model
{
    /**
     * Read statuses used by the list filter and the status form.
     */
    public const STATUSES = ['unread', 'read'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * The customer who sent the message, or null for other senders.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
