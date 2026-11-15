<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
     * Customers whose name, email or phone number contains the search text. Used
     * by the customer list and the global search.
     *
     * @param  Builder<Customer>  $query
     */
    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");

            foreach (self::phoneSearchTerms($search) as $term) {
                $query->orWhere('phone', 'like', "%{$term}%");
            }
        });
    }

    /**
     * Phone numbers are stored without spaces, such as +33639980001. The search is
     * compared without spaces or punctuation, and a leading 0 (06 39 98…) is also
     * tried as +33. A search without digits does not look at phone numbers.
     *
     * @return list<string>
     */
    private static function phoneSearchTerms(string $search): array
    {
        $digits = preg_replace('/[^\d+]/', '', $search);

        if (! preg_match('/\d/', $digits)) {
            return [];
        }

        return str_starts_with($digits, '0') ? [$digits, '+33'.substr($digits, 1)] : [$digits];
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
