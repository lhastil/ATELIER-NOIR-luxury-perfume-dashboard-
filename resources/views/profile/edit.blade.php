<x-layouts.admin title="My profile" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'My profile'],
]">
    <x-page-header eyebrow="Administration" title="My profile" description="Update your name, email address and password." />

    <div class="profile-forms">
        <div>
            <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                @error('photo')
                    <div class="form-alert" role="alert">
                        <p>Your photo was not saved. Please correct the highlighted field below.</p>
                    </div>
                @enderror

                <x-card title="Profile photo" description="Optional. Shown next to your name in the top bar and the sidebar; your initials are used without one.">
                    <div class="image-field profile-photo">
                        <figure class="image-field__preview">
                            <img
                                id="profile-photo-preview"
                                src="{{ $user->profile_photo_url }}"
                                alt=""
                                width="96"
                                height="96"
                                @if (! $user->profile_photo_url) hidden @endif
                            >
                            <span class="image-field__placeholder" @if ($user->profile_photo_url) hidden @endif aria-hidden="true">
                                <x-avatar :name="$user->name" :size="96" />
                            </span>
                            <figcaption id="profile-photo-preview-caption" class="image-field__caption">
                                {{ $user->profile_photo_url ? 'Current photo' : 'No photo yet' }}
                            </figcaption>
                        </figure>

                        <div class="image-field__controls">
                            <div class="field">
                                <label class="field__label" for="photo">{{ $user->profile_photo_url ? 'Replace photo' : 'Upload photo' }}</label>
                                <input
                                    id="photo"
                                    class="field__file"
                                    type="file"
                                    name="photo"
                                    accept="image/jpeg,image/png,image/webp"
                                    aria-describedby="photo-hint @error('photo') photo-error @enderror"
                                    @error('photo') aria-invalid="true" @enderror
                                    data-image-input="profile-photo-preview"
                                >
                                <p id="photo-hint" class="field__hint">JPG, PNG or WebP, up to 2 MB.</p>
                                @error('photo')
                                    <p id="photo-error" class="field__error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </x-card>

                <div class="form-actions">
                    @if ($user->profile_photo_path)
                        {{-- Submits the separate removal form below (forms cannot be nested). --}}
                        <button type="submit" class="button button--ghost" form="remove-photo-form">Remove photo</button>
                    @endif
                    <button type="submit" class="button button--primary">Save photo</button>
                </div>
            </form>

            @if ($user->profile_photo_path)
                <form id="remove-photo-form" method="POST" action="{{ route('profile.photo.destroy') }}" data-confirm="Remove your profile photo?">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </div>

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
                    {{-- Read-only here and never saved by this form: roles change only in User Management, by a Store Manager. --}}
                    <x-form.select name="role" label="Role" :options="App\Models\User::ROLES" :value="$user->role" disabled aria-describedby="role-hint" />
                    <p id="role-hint" class="field__hint form-grid__wide">
                        @can('access-users')
                            Roles are changed in <a href="{{ route('users.edit', $user) }}">User Management</a>.
                        @else
                            Only a Store Manager can change roles.
                        @endcan
                    </p>
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
