<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Key figures, charts and lists for the dashboard, all read from the stored data.
 * Money stays in whole cents until it is formatted for display.
 */
class DashboardController extends Controller
{
    /**
     * Calendar months shown in the revenue chart, including the current month.
     */
    private const REVENUE_MONTHS = 6;

    public function __invoke(): View
    {
        $deliveredOrders = Order::delivered()->with('items')->get();
        $statusCounts = Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('dashboard', [
            'revenueCents' => $deliveredOrders->sum('total_cents'),
            'deliveredOrderCount' => $deliveredOrders->count(),
            'orderCount' => Order::count(),
            'productCount' => Product::count(),
            'activeProductCount' => Product::where('is_active', true)->count(),
            'customerCount' => Customer::count(),
            'revenueByMonth' => $this->revenueByMonth($deliveredOrders),
            'ordersByStatus' => collect(Order::STATUSES)->mapWithKeys(fn (string $status) => [$status => (int) ($statusCounts[$status] ?? 0)]),
            'recentOrders' => Order::with(['customer', 'items'])->latest('placed_at')->latest('id')->limit(5)->get(),
            'bestSellers' => $this->bestSellers(),
        ]);
    }

    /**
     * Delivered revenue in cents for each of the last six calendar months, oldest
     * first, keyed by "Y-m". Revenue counts in the month the order was placed;
     * months without delivered orders are 0.
     *
     * @param  Collection<int, Order>  $deliveredOrders
     * @return array<string, int>
     */
    private function revenueByMonth(Collection $deliveredOrders): array
    {
        $months = [];

        for ($monthsAgo = self::REVENUE_MONTHS - 1; $monthsAgo >= 0; $monthsAgo--) {
            $months[now()->startOfMonth()->subMonths($monthsAgo)->format('Y-m')] = 0;
        }

        foreach ($deliveredOrders as $order) {
            $month = $order->placed_at->format('Y-m');

            if (array_key_exists($month, $months)) {
                $months[$month] += $order->total_cents;
            }
        }

        return $months;
    }

    /**
     * The five products with the most units in delivered orders, from the order
     * item snapshots, so products deleted since keep their place. Ties go by SKU.
     * Each is shown with the name from its most recently placed delivered order.
     *
     * @return Collection<int, OrderItem>
     */
    private function bestSellers(): Collection
    {
        $bestSellers = OrderItem::query()
            ->whereHas('order', fn ($query) => $query->delivered())
            ->selectRaw('product_sku, sum(quantity) as units')
            ->groupBy('product_sku')
            ->orderByDesc('units')
            ->orderBy('product_sku')
            ->limit(5)
            ->get();

        // Oldest first, so each SKU's latest name (latest order, then latest item) is the one kept.
        $names = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereHas('order', fn ($query) => $query->delivered())
            ->whereIn('order_items.product_sku', $bestSellers->pluck('product_sku'))
            ->orderBy('orders.placed_at')
            ->orderBy('orders.id')
            ->orderBy('order_items.id')
            ->pluck('order_items.product_name', 'order_items.product_sku');

        return $bestSellers->each(fn (OrderItem $item) => $item->product_name = $names[$item->product_sku]);
    }
}
