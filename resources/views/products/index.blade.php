<x-layouts.admin title="Products" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Products']]">
    <x-page-header eyebrow="Catalog" title="Products" description="Every fragrance in the catalog, with its price and stock.">
        <x-slot:actions>
            <a class="button button--primary" href="{{ route('products.create') }}">
                <x-icon name="plus" :size="16" />
                Add product
            </a>
        </x-slot:actions>
    </x-page-header>

    @php($filtering = $filters['search'] !== '' || $filters['category'] || $filters['status'])

    <form class="filters" method="GET" action="{{ route('products.index') }}" role="search" aria-label="Filter products">
        <div class="field filters__search">
            <label class="field__label" for="search">Search</label>
            <input id="search" class="field__input" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Name, SKU or brand">
        </div>

        <div class="field">
            <label class="field__label" for="category">Category</label>
            <select id="category" class="field__input field__select" name="category">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['category'] === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label class="field__label" for="status">Status</label>
            <select id="status" class="field__input field__select" name="status">
                <option value="">Any status</option>
                <option value="active" @selected($filters['status'] === 'active')>Active</option>
                <option value="hidden" @selected($filters['status'] === 'hidden')>Hidden</option>
            </select>
        </div>

        <div class="filters__actions">
            <button type="submit" class="button button--secondary">Apply</button>
            @if ($filtering)
                <a class="button button--ghost" href="{{ route('products.index') }}">Reset</a>
            @endif
        </div>
    </form>

    <x-card title="Catalog" :description="$products->total().' '.Str::plural('product', $products->total()).($filtering ? ' match the filters' : '')">
        <x-table caption="Products" :columns="[
            'Product',
            'Brand',
            'Category',
            ['label' => 'Price', 'numeric' => true],
            ['label' => 'Stock', 'numeric' => true],
            'Status',
            ['label' => 'Actions', 'hidden' => true],
        ]">
            @foreach ($products as $product)
                <tr>
                    <td>
                        <div class="product-cell">
                            @if ($product->image_url)
                                <img class="product-thumb" src="{{ $product->image_url }}" alt="" width="44" height="44" loading="lazy">
                            @else
                                <span class="product-thumb product-thumb--empty" aria-hidden="true"><x-icon name="image" :size="18" /></span>
                            @endif
                            <div class="product-cell__text">
                                <a class="product-cell__name" href="{{ route('products.edit', $product) }}">{{ $product->name }}</a>
                                <span class="product-cell__meta">{{ $product->sku }} · {{ $product->concentration }} · {{ $product->volume_ml }} ml</span>
                            </div>
                        </div>
                    </td>
                    <td>{{ $product->brand }}</td>
                    <td>{{ $product->category->name }}</td>
                    <td class="is-numeric">{{ $product->formatted_price }}</td>
                    <td class="is-numeric">
                        @if ($product->stock === 0)
                            <x-badge tone="danger">Out of stock</x-badge>
                        @else
                            {{ $product->stock }}
                        @endif
                    </td>
                    <td>
                        <x-badge :tone="$product->is_active ? 'success' : 'neutral'">{{ $product->is_active ? 'Active' : 'Hidden' }}</x-badge>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a class="icon-button" href="{{ route('products.edit', $product) }}" aria-label="Edit {{ $product->name }}">
                                <x-icon name="pencil" :size="16" />
                            </a>
                            <form method="POST" action="{{ route('products.destroy', $product) }}" data-confirm="Delete “{{ $product->name }}”? This cannot be undone.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-button icon-button--danger" aria-label="Delete {{ $product->name }}">
                                    <x-icon name="trash-2" :size="16" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach

            <x-slot:empty>
                @if ($products->total() > 0)
                    <x-empty-state icon="package" title="This page is empty">
                        <a href="{{ $products->url(1) }}">Go to the first page</a>.
                    </x-empty-state>
                @elseif ($filtering)
                    <x-empty-state icon="search" title="No matching products">
                        Try another search or <a href="{{ route('products.index') }}">reset the filters</a>.
                    </x-empty-state>
                @else
                    <x-empty-state icon="package" title="No products yet">
                        <a href="{{ route('products.create') }}">Add the first product</a> to start the catalog.
                    </x-empty-state>
                @endif
            </x-slot:empty>
        </x-table>

        {{ $products->links('pagination.admin') }}
    </x-card>
</x-layouts.admin>
