<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

#[Fillable(['number', 'customer_id', 'status', 'placed_at'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Order statuses, in the order an order normally moves through them.
     */
    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'placed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * The order total in cents, added up from the stored order items.
     */
    protected function totalCents(): Attribute
    {
        return Attribute::get(fn () => $this->items->sum('line_total_cents'));
    }

    /**
     * Order total in the store currency, e.g. "€169.00".
     */
    protected function formattedTotal(): Attribute
    {
        return Attribute::get(fn () => Number::currency($this->total_cents / 100, in: config('app.currency'), locale: config('app.locale')));
    }

    /**
     * Badge tone for the status: delivered is green, cancelled is red.
     */
    protected function statusTone(): Attribute
    {
        return Attribute::get(fn () => match ($this->status) {
            'delivered' => 'success',
            'cancelled' => 'danger',
            default => 'neutral',
        });
    }
}
