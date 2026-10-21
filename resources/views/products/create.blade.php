<x-layouts.admin title="Add product" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Products', 'url' => route('products.index')],
    ['label' => 'Add product'],
]">
    <x-page-header eyebrow="Catalog" title="Add product" description="Add a new fragrance to the catalog." />

    @include('products._form', [
        'action' => route('products.store'),
        'submitLabel' => 'Add product',
    ])
</x-layouts.admin>
