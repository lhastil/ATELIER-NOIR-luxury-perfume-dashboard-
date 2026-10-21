<x-layouts.admin title="Customers" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Customers']]">
    <x-page-header eyebrow="Clientele" title="Customers" description="The clients of the house, with their contact details and orders." />

    <form class="filters" method="GET" action="{{ route('customers.index') }}" role="search" aria-label="Search customers">
        <div class="field filters__search">
            <label class="field__label" for="search">Search</label>
            <input id="search" class="field__input" type="search" name="search" value="{{ $search }}" placeholder="Name, email or phone">
        </div>

        <div class="filters__actions">
            <button type="submit" class="button button--secondary">Apply</button>
            @if ($search !== '')
                <a class="button button--ghost" href="{{ route('customers.index') }}">Reset</a>
            @endif
        </div>
    </form>

    <x-card title="Customer list" :description="$customers->total().' '.Str::plural('customer', $customers->total()).($search !== '' ? ($customers->total() === 1 ? ' matches' : ' match').' the search' : '')">
        <x-table caption="Customers" :columns="[
            'Customer',
            'Phone',
            ['label' => 'Orders', 'numeric' => true],
            'Last order',
        ]">
            @foreach ($customers as $customer)
                <tr>
                    <td>
                        <a class="table-link" href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a>
                        <span class="table-meta">{{ $customer->email }}</span>
                    </td>
                    <td>{{ $customer->formatted_phone ?? '—' }}</td>
                    <td class="is-numeric">{{ $customer->orders_count }}</td>
                    <td>{{ $customer->orders_max_placed_at ? Carbon\Carbon::parse($customer->orders_max_placed_at)->isoFormat('D MMM YYYY') : '—' }}</td>
                </tr>
            @endforeach

            <x-slot:empty>
                @if ($customers->total() > 0)
                    <x-empty-state icon="users" title="This page is empty">
                        <a href="{{ $customers->url(1) }}">Go to the first page</a>.
                    </x-empty-state>
                @elseif ($search !== '')
                    <x-empty-state icon="search" title="No matching customers">
                        Try another search or <a href="{{ route('customers.index') }}">show all customers</a>.
                    </x-empty-state>
                @else
                    <x-empty-state icon="users" title="No customers yet">
                        Customers appear here once they are added to the database.
                    </x-empty-state>
                @endif
            </x-slot:empty>
        </x-table>

        {{ $customers->links('pagination.admin') }}
    </x-card>
</x-layouts.admin>
