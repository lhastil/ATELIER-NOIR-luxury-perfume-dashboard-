<x-layouts.admin title="User Management" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'User Management']]">
    <x-page-header eyebrow="Administration" title="User Management" description="The accounts that can sign in to the admin panel, and their roles.">
        <x-slot:actions>
            <a class="button button--primary" href="{{ route('users.create') }}">
                <x-icon name="user-plus" :size="16" />
                Add user
            </a>
        </x-slot:actions>
    </x-page-header>

    @error('user')
        <div class="form-alert" role="alert">
            <p>{{ $message }}</p>
        </div>
    @enderror

    <x-card title="Accounts" :description="$users->total().' '.Str::plural('account', $users->total())">
        <x-table caption="User accounts" :columns="[
            'Name',
            'Role',
            ['label' => 'Actions', 'hidden' => true],
        ]">
            @foreach ($users as $user)
                <tr>
                    <td>
                        <a class="table-link" href="{{ route('users.edit', $user) }}">{{ $user->name }}</a>
                        <span class="table-meta">{{ $user->email }}{{ $user->is(auth()->user()) ? ' · You' : '' }}</span>
                    </td>
                    <td><x-badge :tone="$user->isStoreManager() ? 'success' : 'neutral'">{{ $user->role_label }}</x-badge></td>
                    <td>
                        <div class="row-actions">
                            <a class="icon-button" href="{{ route('users.edit', $user) }}" aria-label="Edit {{ $user->name }}">
                                <x-icon name="pencil" :size="16" />
                            </a>
                            @unless ($user->is(auth()->user()))
                                <form method="POST" action="{{ route('users.destroy', $user) }}" data-confirm="Delete the account of &quot;{{ $user->name }}&quot;? They will no longer be able to sign in.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-button icon-button--danger" aria-label="Delete {{ $user->name }}">
                                        <x-icon name="trash-2" :size="16" />
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        {{ $users->links('pagination.admin') }}
    </x-card>
</x-layouts.admin>
