<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Orders are read-only in the panel apart from their status.
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        // A malformed query such as ?search[]=x counts as no search.
        $search = is_string($request->query('search')) ? trim($request->query('search')) : '';
        $status = in_array($request->query('status'), Order::STATUSES, true) ? $request->query('status') : null;

        $orders = Order::query()
            ->with(['customer', 'items'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            }))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('placed_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items.product']);

        return view('orders.show', ['order' => $order]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
        ]);

        $order->update($validated);

        return redirect()->route('orders.show', $order)->with('status', "Order {$order->number} is now marked as ".$order->status.'.');
    }
}
