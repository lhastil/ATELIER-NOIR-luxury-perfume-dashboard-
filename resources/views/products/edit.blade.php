<x-layouts.admin :title="'Edit '.$product->name" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Products', 'url' => route('products.index')],
    ['label' => $product->name],
]">
    <x-page-header eyebrow="Catalog · {{ $product->sku }}" :title="$product->name" description="Update the product details, price, stock or image.">
        <x-slot:actions>
            <form method="POST" action="{{ route('products.destroy', $product) }}" data-confirm="Delete “{{ $product->name }}”? This cannot be undone.">
                @csrf
                @method('DELETE')
                <button type="submit" class="button button--danger">
                    <x-icon name="trash-2" :size="16" />
                    Delete
                </button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @include('products._form', [
        'action' => route('products.update', $product),
        'submitLabel' => 'Save changes',
    ])
</x-layouts.admin>
