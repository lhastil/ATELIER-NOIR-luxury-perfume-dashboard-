<x-layouts.admin title="My profile" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'My profile'],
]">
    <x-page-header eyebrow="Administration" title="My profile" description="Update your name, email address and password." />

    <div class="profile-forms">
        <form method="POST" action="{{ route('profile.update') }}" novalidate>
            @csrf
            @method('PATCH')

            @if ($errors->hasAny(['name', 'email']))
                <div class="form-alert" role="alert">
                    <p>Your profile was not saved. Please correct the highlighted fields below.</p>
                </div>
            @endif

            <x-card title="Profile" :description="$user->created_at ? 'Account created on '.$user->created_at->isoFormat('D MMMM YYYY').'.' : null">
                <div class="form-grid">
                    <x-form.input name="name" label="Full name" :value="$user->name" required maxlength="255" autocomplete="name" />
                    <x-form.input name="email" label="Email address" type="email" :value="$user->email" required maxlength="255" autocomplete="email" />
                </div>
            </x-card>

            <div class="form-actions">
                <button type="submit" class="button button--primary">Save profile</button>
            </div>
        </form>

        <form method="POST" action="{{ route('profile.password.update') }}" novalidate>
            @csrf
            @method('PUT')

            @if ($errors->hasAny(['current_password', 'password', 'password_confirmation']))
                <div class="form-alert" role="alert">
                    <p>Your password was not changed. Please correct the highlighted fields below.</p>
                </div>
            @endif

            <x-card title="Change password" description="Enter your current password, then choose a new one.">
                {{-- Password fields are never refilled after a failed submission. --}}
                <div class="form-grid">
                    <div class="form-grid__wide">
                        <x-form.input name="current_password" label="Current password" type="password" value="" required autocomplete="current-password" />
                    </div>
                    <x-form.input name="password" label="New password" type="password" value="" required autocomplete="new-password" hint="At least 8 characters." />
                    <x-form.input name="password_confirmation" label="Confirm new password" type="password" value="" required autocomplete="new-password" />
                </div>
            </x-card>

            <div class="form-actions">
                <button type="submit" class="button button--primary">Change password</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
