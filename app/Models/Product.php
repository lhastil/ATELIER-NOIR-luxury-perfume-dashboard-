<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

#[Fillable([
    'category_id', 'name', 'sku', 'brand', 'concentration', 'volume_ml',
    'price', 'stock', 'is_active', 'description', 'image_path',
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
     * Folder on the public disk that holds uploaded product images.
     */
    public const IMAGE_DIRECTORY = 'products';

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

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image_path ? Storage::disk('public')->url($this->image_path) : null);
    }
}
