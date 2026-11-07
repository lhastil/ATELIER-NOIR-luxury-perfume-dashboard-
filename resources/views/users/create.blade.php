<x-layouts.admin title="Add user" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Add user'],
]">
    <x-page-header eyebrow="Administration" title="Add user" description="Create an account that can sign in to the admin panel." />

    <form method="POST" action="{{ route('users.store') }}" novalidate>
        @csrf

        @if ($errors->any())
            <div class="form-alert" role="alert">
                <p>Please correct the highlighted {{ Str::plural('field', $errors->count()) }} below.</p>
            </div>
        @endif

        <x-card title="Account details">
            <div class="form-grid">
                <x-form.input name="name" label="Full name" required maxlength="255" autocomplete="name" />
                <x-form.input name="email" label="Email address" type="email" required maxlength="255" autocomplete="off" />
                {{-- Password fields are never refilled after a failed submission. --}}
                <x-form.input name="password" label="Password" type="password" value="" required autocomplete="new-password" hint="At least 8 characters." />
                <x-form.input name="password_confirmation" label="Confirm password" type="password" value="" required autocomplete="new-password" />
            </div>
        </x-card>

        <div class="form-actions">
            <a class="button button--ghost" href="{{ route('dashboard') }}">Cancel</a>
            <button type="submit" class="button button--primary">Add user</button>
        </div>
    </form>
</x-layouts.admin>
