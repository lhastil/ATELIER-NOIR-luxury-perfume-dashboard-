<x-layouts.admin title="Categories" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Categories']]">
    <x-page-header eyebrow="Catalog" title="Categories" description="The perfume families products are grouped by.">
        <x-slot:actions>
            <a class="button button--primary" href="{{ route('categories.create') }}">
                <x-icon name="plus" :size="16" />
                Add category
            </a>
        </x-slot:actions>
    </x-page-header>

    @error('category')
        <div class="form-alert" role="alert">
            <p>{{ $message }}</p>
        </div>
    @enderror

    <x-card title="Category list" :description="$categories->total().' '.Str::plural('category', $categories->total())">
        <x-table caption="Categories" :columns="[
            'Category',
            ['label' => 'Products', 'numeric' => true],
            ['label' => 'Actions', 'hidden' => true],
        ]">
            @foreach ($categories as $category)
                <tr>
                    <td><a class="table-link" href="{{ route('categories.edit', $category) }}">{{ $category->name }}</a></td>
                    <td class="is-numeric">
                        @if ($category->products_count > 0)
                            <a class="table-link" href="{{ route('products.index', ['category' => $category->id]) }}">{{ $category->products_count }}</a>
                        @else
                            0
                        @endif
                    </td>
                    <td>
                        <div class="row-actions">
                            <a class="icon-button" href="{{ route('categories.edit', $category) }}" aria-label="Edit {{ $category->name }}">
                                <x-icon name="pencil" :size="16" />
                            </a>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" data-confirm="Delete the category &quot;{{ $category->name }}&quot;? This cannot be undone.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-button icon-button--danger" aria-label="Delete {{ $category->name }}">
                                    <x-icon name="trash-2" :size="16" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach

            <x-slot:empty>
                @if ($categories->total() > 0)
                    <x-empty-state icon="layers" title="This page is empty">
                        <a href="{{ $categories->url(1) }}">Go to the first page</a>.
                    </x-empty-state>
                @else
                    <x-empty-state icon="layers" title="No categories yet">
                        <a href="{{ route('categories.create') }}">Add the first category</a> to group the catalog.
                    </x-empty-state>
                @endif
            </x-slot:empty>
        </x-table>

        {{ $categories->links('pagination.admin') }}
    </x-card>
</x-layouts.admin>
