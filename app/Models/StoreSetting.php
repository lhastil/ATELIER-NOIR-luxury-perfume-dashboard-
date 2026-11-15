<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * The store's basic information. There is only ever one row: use
 * StoreSetting::current() to read it instead of creating new records.
 */
#[Fillable(['store_name', 'contact_email', 'phone', 'address'])]
class StoreSetting extends Model
{
    /**
     * Folder on the "public" disk where uploaded logos are stored.
     */
    public const LOGO_DIRECTORY = 'store-logos';

    /**
     * The store's settings row, or a new unsaved one before the first save.
     */
    public static function current(): self
    {
        return static::query()->oldest('id')->first() ?? new static;
    }

    /**
     * Public address of the logo, or null without one.
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo_path
            ? Storage::disk('public')->url($this->logo_path)
            : null);
    }
}
