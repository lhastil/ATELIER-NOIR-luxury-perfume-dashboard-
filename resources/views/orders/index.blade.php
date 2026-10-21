@use('App\Models\Order')

<x-layouts.admin title="Orders" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Orders']]">
    <x-page-header eyebrow="Sales" title="Orders" description="Every order placed with the house, newest first." />

    @php($filtering = $filters['search'] !== '' || $filters['status'])

    <form class="filters" method="GET" action="{{ route('orders.index') }}" role="search" aria-label="Filter orders">
        <div class="field filters__search">
            <label class="field__label" for="search">Search</label>
            <input id="search" class="field__input" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Order number, customer name or email">
        </div>

        <div class="field">
            <label class="field__label" for="status">Status</label>
            <select id="status" class="field__input field__select" name="status">
                <option value="">Any status</option>
                @foreach (Order::STATUSES as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>

        <div class="filters__actions">
            <button type="submit" class="button button--secondary">Apply</button>
            @if ($filtering)
                <a class="button button--ghost" href="{{ route('orders.index') }}">Reset</a>
            @endif
        </div>
    </form>

    <x-card title="Order list" :description="$orders->total().' '.Str::plural('order', $orders->total()).($filtering ? ($orders->total() === 1 ? ' matches' : ' match').' the filters' : '')">
        <x-table caption="Orders" :columns="[
            'Order',
            'Customer',
            'Date',
            ['label' => 'Items', 'numeric' => true],
            ['label' => 'Total', 'numeric' => true],
            'Status',
        ]">
            @foreach ($orders as $order)
                <tr>
                    <td><a class="table-link" href="{{ route('orders.show', $order) }}">{{ $order->number }}</a></td>
                    <td>
                        <a class="table-link" href="{{ route('customers.show', $order->customer) }}">{{ $order->customer->name }}</a>
                        <span class="table-meta">{{ $order->customer->email }}</span>
                    </td>
                    <td>{{ $order->placed_at->isoFormat('D MMM YYYY') }}</td>
                    <td class="is-numeric">{{ $order->items->sum('quantity') }}</td>
                    <td class="is-numeric">{{ $order->formatted_total }}</td>
                    <td><x-badge :tone="$order->status_tone">{{ ucfirst($order->status) }}</x-badge></td>
                </tr>
            @endforeach

            <x-slot:empty>
                @if ($orders->total() > 0)
                    <x-empty-state icon="shopping-bag" title="This page is empty">
                        <a href="{{ $orders->url(1) }}">Go to the first page</a>.
                    </x-empty-state>
                @elseif ($filtering)
                    <x-empty-state icon="search" title="No matching orders">
                        Try another search or <a href="{{ route('orders.index') }}">reset the filters</a>.
                    </x-empty-state>
                @else
                    <x-empty-state icon="shopping-bag" title="No orders yet">
                        Orders appear here once they are added to the database.
                    </x-empty-state>
                @endif
            </x-slot:empty>
        </x-table>

        {{ $orders->links('pagination.admin') }}
    </x-card>
</x-layouts.admin>
