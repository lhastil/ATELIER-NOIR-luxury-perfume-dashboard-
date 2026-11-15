<x-layouts.admin :title="'Edit '.$category->name" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Categories', 'url' => route('categories.index')],
    ['label' => $category->name],
]">
    <x-page-header eyebrow="Catalog" :title="$category->name" description="Rename this category. Its products keep it under the new name.">
        <x-slot:actions>
            <form method="POST" action="{{ route('categories.destroy', $category) }}" data-confirm="Delete the category &quot;{{ $category->name }}&quot;? This cannot be undone.">
                @csrf
                @method('DELETE')
                <button type="submit" class="button button--danger">
                    <x-icon name="trash-2" :size="16" />
                    Delete
                </button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @include('categories._form', [
        'action' => route('categories.update', $category),
        'submitLabel' => 'Save changes',
    ])
</x-layouts.admin>
