<x-layouts.admin :title="$customer->name" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Customers', 'url' => route('customers.index')],
    ['label' => $customer->name],
]">
    <x-page-header eyebrow="Clientele" :title="$customer->name" description="Contact details and every order placed by this customer." />

    <div class="detail-layout">
        <x-card title="Order history" :description="$customer->orders->count().' '.Str::plural('order', $customer->orders->count())">
            <x-table caption="Orders placed by {{ $customer->name }}" :columns="[
                'Order',
                'Date',
                ['label' => 'Items', 'numeric' => true],
                ['label' => 'Total', 'numeric' => true],
                'Status',
            ]">
                @foreach ($customer->orders as $order)
                    <tr>
                        <td><a class="table-link" href="{{ route('orders.show', $order) }}">{{ $order->number }}</a></td>
                        <td>{{ $order->placed_at->isoFormat('D MMM YYYY') }}</td>
                        <td class="is-numeric">{{ $order->items->sum('quantity') }}</td>
                        <td class="is-numeric">{{ $order->formatted_total }}</td>
                        <td><x-badge :tone="$order->status_tone">{{ ucfirst($order->status) }}</x-badge></td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-empty-state icon="shopping-bag" title="No orders yet">
                        Orders placed by this customer will be listed here.
                    </x-empty-state>
                </x-slot:empty>
            </x-table>
        </x-card>

        <div class="detail-layout__aside">
            <x-card title="Contact">
                <dl class="detail-list">
                    <div>
                        <dt>Email</dt>
                        <dd><a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a></dd>
                    </div>
                    <div>
                        <dt>Phone</dt>
                        <dd>
                            @if ($customer->phone)
                                <a href="tel:{{ str_replace(' ', '', $customer->phone) }}">{{ $customer->formatted_phone }}</a>
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
