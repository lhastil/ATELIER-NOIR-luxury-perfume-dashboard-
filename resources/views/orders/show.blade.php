@use('App\Models\Order')

<x-layouts.admin :title="'Order '.$order->number" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Orders', 'url' => route('orders.index')],
    ['label' => $order->number],
]">
    <x-page-header :eyebrow="'Placed '.$order->placed_at->isoFormat('D MMMM YYYY, HH:mm')" :title="'Order '.$order->number" description="The products ordered, the customer and the order status." />

    <div class="detail-layout">
        <x-card title="Items" :description="$order->items->sum('quantity').' '.Str::plural('item', $order->items->sum('quantity')).' at the prices paid when the order was placed'">
            <x-table caption="Items in order {{ $order->number }}" :columns="[
                'Product',
                ['label' => 'Quantity', 'numeric' => true],
                ['label' => 'Unit price', 'numeric' => true],
                ['label' => 'Line total', 'numeric' => true],
            ]">
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            @if ($item->product)
                                <a class="table-link" href="{{ route('products.edit', $item->product) }}">{{ $item->product_name }}</a>
                            @else
                                {{ $item->product_name }}
                            @endif
                            <span class="table-meta">{{ $item->product_sku }}{{ $item->product ? '' : ' · No longer in the catalog' }}</span>
                        </td>
                        <td class="is-numeric">{{ $item->quantity }}</td>
                        <td class="is-numeric">{{ $item->formatted_unit_price }}</td>
                        <td class="is-numeric">{{ $item->formatted_line_total }}</td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-empty-state icon="package" title="No items">
                        This order has no items recorded.
                    </x-empty-state>
                </x-slot:empty>
            </x-table>

            <dl class="order-total">
                <dt>Order total</dt>
                <dd>{{ $order->formatted_total }}</dd>
            </dl>
        </x-card>

        <div class="detail-layout__aside">
            <x-card title="Status">
                <form class="status-form" method="POST" action="{{ route('orders.status.update', $order) }}">
                    @csrf
                    @method('PATCH')

                    <p>Current status <x-badge :tone="$order->status_tone">{{ ucfirst($order->status) }}</x-badge></p>

                    <x-form.select name="status" label="Change status to" :options="array_combine(Order::STATUSES, array_map('ucfirst', Order::STATUSES))" :value="$order->status" required />

                    <button type="submit" class="button button--primary">Update status</button>
                </form>
            </x-card>

            <x-card title="Customer">
                <dl class="detail-list">
                    <div>
                        <dt>Name</dt>
                        <dd><a href="{{ route('customers.show', $order->customer) }}">{{ $order->customer->name }}</a></dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd><a href="mailto:{{ $order->customer->email }}">{{ $order->customer->email }}</a></dd>
                    </div>
                    <div>
                        <dt>Phone</dt>
                        <dd>
                            @if ($order->customer->phone)
                                <a href="tel:{{ str_replace(' ', '', $order->customer->phone) }}">{{ $order->customer->formatted_phone }}</a>
                            @else
                                Not provided
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-layouts.admin>
