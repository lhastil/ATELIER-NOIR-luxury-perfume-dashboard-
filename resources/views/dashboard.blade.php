@php
    $user = auth()->user();
@endphp

<x-layouts.admin title="Dashboard">
    <x-page-header
        :eyebrow="now()->isoFormat('dddd D MMMM YYYY')"
        :title="$user ? 'Welcome back, '.$user->name : 'Welcome'"
        description="An overview of sales, orders and the catalog."
    />

    {{--
        No business data exists yet, so every figure, chart and list shows an empty
        state. Values from real database queries replace them once orders, products
        and customers are stored; nothing here is sample data.
    --}}
    <div class="dashboard">
        <section aria-labelledby="key-figures-heading">
            <h2 id="key-figures-heading" class="visually-hidden">Key figures</h2>

            <ul class="stat-grid" role="list">
                <li><x-stat-card label="Total Revenue" hint="No delivered orders yet" /></li>
                <li><x-stat-card label="Total Orders" hint="No orders recorded yet" /></li>
                <li><x-stat-card label="Products in Catalog" hint="No products in the catalog yet" /></li>
                <li><x-stat-card label="Total Customers" hint="No customers recorded yet" /></li>
            </ul>
        </section>

        <div class="dashboard-row">
            <x-chart-panel title="Revenue" description="Revenue from delivered orders over time.">
                <x-empty-state icon="chart-line" title="No revenue yet">
                    Revenue appears here once orders are delivered.
                </x-empty-state>
            </x-chart-panel>

            <x-chart-panel title="Order Analytics" description="Orders placed over time.">
                <x-empty-state icon="chart-column" title="No orders yet">
                    Order activity appears here once orders are placed.
                </x-empty-state>
            </x-chart-panel>
        </div>

        <div class="dashboard-row">
            <x-card title="Recent Orders" description="The latest orders placed in the store.">
                <x-table caption="Recent orders" :columns="[
                    'Order',
                    'Customer',
                    'Date',
                    ['label' => 'Total', 'numeric' => true],
                    'Status',
                    ['label' => 'Actions', 'hidden' => true],
                ]">
                    <x-slot:empty>
                        <x-empty-state icon="shopping-bag" title="No orders yet">
                            New orders will be listed here.
                        </x-empty-state>
                    </x-slot:empty>
                </x-table>
            </x-card>

            <x-card title="Best Sellers" description="The best-selling fragrances.">
                <x-empty-state icon="award" title="Nothing to rank yet">
                    Best sellers appear here once orders exist.
                </x-empty-state>
            </x-card>
        </div>
    </div>
</x-layouts.admin>
