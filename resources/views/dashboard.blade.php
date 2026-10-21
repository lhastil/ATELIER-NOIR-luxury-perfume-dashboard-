@use('Illuminate\Support\Carbon')
@use('Illuminate\Support\Number')

@php
    $user = auth()->user();
    $currency = config('app.currency');
    $money = fn (int $cents) => Number::currency($cents / 100, in: $currency, locale: config('app.locale'));

    $monthLabels = array_map(fn (string $month) => Carbon::createFromFormat('Y-m-d', $month.'-01')->isoFormat('MMM YYYY'), array_keys($revenueByMonth));
    $hasRecentRevenue = array_sum($revenueByMonth) > 0;

    // Chart data for resources/js/ui/charts.js; money is passed in cents.
    $revenueChart = ['format' => 'currency', 'currency' => $currency, 'labels' => $monthLabels, 'values' => array_values($revenueByMonth)];
    $statusChart = [
        'format' => 'count',
        'horizontal' => true,
        'labels' => $ordersByStatus->keys()->map(fn (string $status) => ucfirst($status))->all(),
        'values' => $ordersByStatus->values()->all(),
        'colors' => $ordersByStatus->keys()->map(fn (string $status) => match ($status) {
            'delivered' => '--color-success',
            'cancelled' => '--color-danger',
            default => '--color-accent',
        })->all(),
    ];
@endphp

<x-layouts.admin title="Dashboard">
    <x-page-header
        :eyebrow="now()->isoFormat('dddd D MMMM YYYY')"
        :title="$user ? 'Welcome back, '.$user->name : 'Welcome'"
        description="An overview of sales, orders and the catalog."
    />

    <div class="dashboard">
        <section aria-labelledby="key-figures-heading">
            <h2 id="key-figures-heading" class="visually-hidden">Key figures</h2>

            <ul class="stat-grid" role="list">
                <li><x-stat-card label="Total Revenue" :value="$money($revenueCents)" :hint="$deliveredOrderCount > 0 ? 'From '.$deliveredOrderCount.' delivered '.Str::plural('order', $deliveredOrderCount) : 'No delivered orders yet'" /></li>
                <li><x-stat-card label="Total Orders" :value="Number::format($orderCount)" hint="Across all statuses" /></li>
                <li><x-stat-card label="Products in Catalog" :value="Number::format($productCount)" :hint="$activeProductCount.' active'" /></li>
                <li><x-stat-card label="Total Customers" :value="Number::format($customerCount)" /></li>
            </ul>
        </section>

        <div class="dashboard-row">
            <x-chart-panel title="Revenue" description="Revenue from delivered orders by the month they were placed, over the last six months.">
                @if ($hasRecentRevenue)
                    <div class="chart-figure">
                        <div class="chart">
                            <canvas role="img" aria-label="Bar chart of delivered revenue per month, {{ $monthLabels[0] }} to {{ end($monthLabels) }}. The figures are listed below." data-chart="{{ json_encode($revenueChart) }}"></canvas>
                        </div>

                        <p class="chart-figure__fallback">The chart cannot be shown, so its figures are listed below.</p>

                        <div class="chart-figure__data">
                            <table class="table">
                                <caption>Delivered revenue per month</caption>
                                <thead><tr><th scope="col">Month</th><th scope="col" class="is-numeric">Revenue</th></tr></thead>
                                <tbody>
                                    @foreach ($revenueByMonth as $cents)
                                        <tr><th scope="row">{{ $monthLabels[$loop->index] }}</th><td class="is-numeric">{{ $money($cents) }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <x-empty-state icon="chart-line" title="No revenue in the last six months">
                        Revenue appears here once orders placed in this period are delivered.
                    </x-empty-state>
                @endif
            </x-chart-panel>

            <x-chart-panel title="Order Analytics" description="Every order, counted by its current status.">
                @if ($orderCount > 0)
                    <div class="chart-figure">
                        <div class="chart">
                            <canvas role="img" aria-label="Bar chart of orders per status. The figures are listed below." data-chart="{{ json_encode($statusChart) }}"></canvas>
                        </div>

                        <p class="chart-figure__fallback">The chart cannot be shown, so its figures are listed below.</p>

                        <div class="chart-figure__data">
                            <table class="table">
                                <caption>Orders per status</caption>
                                <thead><tr><th scope="col">Status</th><th scope="col" class="is-numeric">Orders</th></tr></thead>
                                <tbody>
                                    @foreach ($ordersByStatus as $status => $count)
                                        <tr><th scope="row">{{ ucfirst($status) }}</th><td class="is-numeric">{{ $count }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <x-empty-state icon="chart-column" title="No orders yet">
                        Order activity appears here once orders are placed.
                    </x-empty-state>
                @endif
            </x-chart-panel>
        </div>

        <div class="dashboard-row">
            <x-card title="Recent Orders" description="The latest orders placed in the store.">
                <x-table class="recent-orders" caption="Recent orders" :columns="[
                    'Order',
                    'Customer',
                    'Date',
                    ['label' => 'Total', 'numeric' => true],
                    'Status',
                    ['label' => 'Actions', 'hidden' => true],
                ]">
                    @foreach ($recentOrders as $order)
                        <tr>
                            <td><a class="table-link" href="{{ route('orders.show', $order) }}">{{ $order->number }}</a></td>
                            <td><a class="table-link" href="{{ route('customers.show', $order->customer) }}">{{ $order->customer->name }}</a></td>
                            <td>{{ $order->placed_at->isoFormat('D MMM YYYY') }}</td>
                            <td class="is-numeric">{{ $order->formatted_total }}</td>
                            <td><x-badge :tone="$order->status_tone">{{ ucfirst($order->status) }}</x-badge></td>
                            <td><a class="table-link" href="{{ route('orders.show', $order) }}">View<span class="visually-hidden"> order {{ $order->number }}</span></a></td>
                        </tr>
                    @endforeach

                    <x-slot:empty>
                        <x-empty-state icon="shopping-bag" title="No orders yet">
                            New orders will be listed here.
                        </x-empty-state>
                    </x-slot:empty>
                </x-table>

                @if ($recentOrders->isNotEmpty())
                    <p class="card-footer-link"><a class="table-link" href="{{ route('orders.index') }}">View all orders</a></p>
                @endif
            </x-card>

            <x-card title="Best Sellers" description="Units sold in delivered orders.">
                @if ($bestSellers->isNotEmpty())
                    <ol class="best-sellers" role="list">
                        @foreach ($bestSellers as $item)
                            <li class="best-sellers__item">
                                <span class="best-sellers__rank" aria-hidden="true">{{ $loop->iteration }}</span>
                                <span class="best-sellers__product">
                                    <span class="best-sellers__name">{{ $item->product_name }}</span>
                                    <span class="table-meta">{{ $item->product_sku }}</span>
                                </span>
                                <span class="best-sellers__units">{{ (int) $item->units }} {{ Str::plural('unit', (int) $item->units) }}</span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <x-empty-state icon="award" title="Nothing to rank yet">
                        Best sellers appear here once orders are delivered.
                    </x-empty-state>
                @endif
            </x-card>
        </div>
    </div>
</x-layouts.admin>
