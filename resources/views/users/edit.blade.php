<x-layouts.admin :title="'Edit '.$user->name" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'User Management', 'url' => route('users.index')],
    ['label' => $user->name],
]">
    <x-page-header eyebrow="Administration" :title="$user->name" description="Update this account's name, email address or role.">
        @unless ($user->is(auth()->user()))
            <x-slot:actions>
                <form method="POST" action="{{ route('users.destroy', $user) }}" data-confirm="Delete the account of &quot;{{ $user->name }}&quot;? They will no longer be able to sign in.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="button button--danger">
                        <x-icon name="trash-2" :size="16" />
                        Delete
                    </button>
                </form>
            </x-slot:actions>
        @endunless
    </x-page-header>

    @include('users._form', [
        'action' => route('users.update', $user),
        'submitLabel' => 'Save changes',
    ])
</x-layouts.admin>
