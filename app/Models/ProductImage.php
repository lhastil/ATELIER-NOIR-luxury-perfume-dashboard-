<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;

/**
 * The uploaded image of a product, stored as bytes in the database.
 */
#[Fillable(['mime_type', 'data'])]
#[Hidden(['data'])]
class ProductImage extends Model
{
    /**
     * Image types that can be stored and served (matching the form validation).
     */
    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The attributes to store for an uploaded file. The type is read from the
     * file's content, never from the name or the type the browser reported.
     *
     * @return array{mime_type: string, data: string}
     */
    public static function attributesFor(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType();

        if (! in_array($mimeType, self::MIME_TYPES, true)) {
            throw new \InvalidArgumentException("Unsupported image type [{$mimeType}].");
        }

        return ['mime_type' => $mimeType, 'data' => $file->getContent()];
    }
}
