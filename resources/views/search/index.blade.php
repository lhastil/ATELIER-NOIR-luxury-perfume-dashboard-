@php($title = $search !== '' ? 'Results for "'.$search.'"' : 'Search')

<x-layouts.admin title="Search" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Search']]">
    <x-page-header
        eyebrow="Search"
        :title="$title"
        description="Products, categories, customers, orders and messages. Up to five matches are shown for each."
    />

    <x-card title="Results">
        @include('search._results')
    </x-card>
</x-layouts.admin>
