<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

/**
 * One product line of an order. The product name, SKU and unit price are copied
 * when the order is placed, so later product changes do not alter the order.
 */
#[Fillable(['order_id', 'product_id', 'product_name', 'product_sku', 'unit_price', 'quantity'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The catalog product, or null once that product has been deleted.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Quantity × unit price in whole cents, so decimal prices add up exactly.
     */
    protected function lineTotalCents(): Attribute
    {
        return Attribute::get(fn () => (int) round((float) $this->unit_price * 100) * $this->quantity);
    }

    protected function formattedUnitPrice(): Attribute
    {
        return Attribute::get(fn () => Number::currency((float) $this->unit_price, in: config('app.currency'), locale: config('app.locale')));
    }

    protected function formattedLineTotal(): Attribute
    {
        return Attribute::get(fn () => Number::currency($this->line_total_cents / 100, in: config('app.currency'), locale: config('app.locale')));
    }
}
