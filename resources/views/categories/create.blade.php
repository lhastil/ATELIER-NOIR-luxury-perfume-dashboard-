<x-layouts.admin title="Add category" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Categories', 'url' => route('categories.index')],
    ['label' => 'Add category'],
]">
    <x-page-header eyebrow="Catalog" title="Add category" description="Add a new perfume family for grouping products." />

    @include('categories._form', [
        'action' => route('categories.store'),
        'submitLabel' => 'Add category',
    ])
</x-layouts.admin>
