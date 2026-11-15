<x-layouts.admin title="Add user" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'User Management', 'url' => route('users.index')],
    ['label' => 'Add user'],
]">
    <x-page-header eyebrow="Administration" title="Add user" description="Create an account that can sign in to the admin panel, and choose its role." />

    @include('users._form', [
        'action' => route('users.store'),
        'submitLabel' => 'Add user',
    ])
</x-layouts.admin>
