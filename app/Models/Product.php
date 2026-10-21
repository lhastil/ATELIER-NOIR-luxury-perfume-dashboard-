<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Number;

#[Fillable([
    'category_id', 'name', 'sku', 'brand', 'concentration', 'volume_ml',
    'price', 'stock', 'is_active', 'description',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Fragrance concentrations offered in the product form.
     */
    public const CONCENTRATIONS = ['Parfum', 'Eau de Parfum', 'Eau de Toilette', 'Eau de Cologne'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'volume_ml' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Price in the store currency, e.g. "€84.00".
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::get(fn () => Number::currency((float) $this->price, in: config('app.currency'), locale: config('app.locale')));
    }

    /**
     * The uploaded image, stored in the database. Lists load it without its
     * bytes (see ProductController), so only the image route reads the data.
     *
     * @return HasOne<ProductImage, $this>
     */
    public function image(): HasOne
    {
        return $this->hasOne(ProductImage::class);
    }

    /**
     * Address of the image, or null without one. It includes the image's id,
     * which changes whenever the image is replaced, so browsers can cache each
     * version for a long time.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image
            ? route('products.image', ['product' => $this, 'v' => $this->image->id])
            : null);
    }
}
