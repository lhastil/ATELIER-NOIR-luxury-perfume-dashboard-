<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customers are read-only in the panel: a searchable list and a details page.
 */
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        // A malformed query such as ?search[]=x counts as no search.
        $search = is_string($request->query('search')) ? trim($request->query('search')) : '';

        $customers = Customer::query()
            ->withCount('orders')
            ->withMax('orders', 'placed_at')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

                foreach ($this->phoneSearchTerms($search) as $term) {
                    $query->orWhere('phone', 'like', "%{$term}%");
                }
            }))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    /**
     * Phone numbers are stored without spaces, such as +33639980001. The search is
     * compared without spaces or punctuation, and a leading 0 (06 39 98…) is also
     * tried as +33. A search without digits does not look at phone numbers.
     *
     * @return list<string>
     */
    private function phoneSearchTerms(string $search): array
    {
        $digits = preg_replace('/[^\d+]/', '', $search);

        if (! preg_match('/\d/', $digits)) {
            return [];
        }

        return str_starts_with($digits, '0') ? [$digits, '+33'.substr($digits, 1)] : [$digits];
    }

    public function show(Customer $customer): View
    {
        $customer->load(['orders' => fn ($query) => $query->with('items')->latest('placed_at')->latest('id')]);

        return view('customers.show', ['customer' => $customer]);
    }
}
