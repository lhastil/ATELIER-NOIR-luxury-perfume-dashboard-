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
            // Name, email or phone; see Customer::scopeSearch().
            ->when($search !== '', fn ($query) => $query->search($search))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    public function show(Customer $customer): View
    {
        $customer->load(['orders' => fn ($query) => $query->with('items')->latest('placed_at')->latest('id')]);

        return view('customers.show', ['customer' => $customer]);
    }
}
