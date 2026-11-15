{{-- User form shared by the "Add user" and "Edit user" pages (Store Managers only). --}}
@use('App\Models\User')

<form method="POST" action="{{ $action }}" novalidate>
    @csrf
    @if ($user->exists)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="form-alert" role="alert">
            <p>Please correct the highlighted {{ Str::plural('field', $errors->count()) }} below.</p>
        </div>
    @endif

    <x-card title="Account details">
        <div class="form-grid">
            <x-form.input name="name" label="Full name" :value="$user->name" required maxlength="255" autocomplete="off" />
            <x-form.input name="email" label="Email address" type="email" :value="$user->email" required maxlength="255" autocomplete="off" />
            <x-form.select
                name="role"
                label="Role"
                :options="User::ROLES"
                :value="$user->role"
                placeholder="Choose a role"
                required
            />
            @unless ($user->exists)
                {{-- Password fields are never refilled after a failed submission. --}}
                <x-form.input name="password" label="Password" type="password" value="" required autocomplete="new-password" hint="At least 8 characters." />
                <x-form.input name="password_confirmation" label="Confirm password" type="password" value="" required autocomplete="new-password" />
            @endunless
        </div>
        <p class="field__hint">Store Managers have full access. Admins manage the catalog, orders, customers and messages. Sellers work with products, orders and messages.</p>
    </x-card>

    <div class="form-actions">
        <a class="button button--ghost" href="{{ route('users.index') }}">Cancel</a>
        <button type="submit" class="button button--primary">{{ $submitLabel }}</button>
    </div>
</form>
