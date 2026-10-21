<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'phone'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Phone numbers are stored in international format (+33639980001); French
     * numbers are shown in the national format, e.g. "06 39 98 00 01".
     */
    protected function formattedPhone(): Attribute
    {
        return Attribute::get(function () {
            if ($this->phone && preg_match('/^\+33(\d{9})$/', $this->phone, $matches)) {
                return trim(chunk_split('0'.$matches[1], 2, ' '));
            }

            return $this->phone;
        });
    }
}
