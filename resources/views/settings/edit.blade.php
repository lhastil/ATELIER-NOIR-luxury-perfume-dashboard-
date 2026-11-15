<x-layouts.admin title="Store settings" :breadcrumbs="[
    ['label' => 'Dashboard', 'url' => route('dashboard')],
    ['label' => 'Store settings'],
]">
    <x-page-header eyebrow="Administration" title="Store settings" description="Update the store's name, contact details and logo." />

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PATCH')

        @if ($errors->any())
            <div class="form-alert" role="alert">
                <p>The settings were not saved. Please correct the highlighted {{ Str::plural('field', $errors->count()) }} below.</p>
            </div>
        @endif

        <div class="settings-form__sections">
            <x-card title="Store details" description="Shown to customers wherever the store's contact information appears.">
                <div class="form-grid">
                    <x-form.input name="store_name" label="Store name" :value="$settings->store_name" required maxlength="255" autocomplete="organization" />
                    <x-form.input name="contact_email" label="Contact email" type="email" :value="$settings->contact_email" required maxlength="255" autocomplete="email" />
                    <x-form.input name="phone" label="Phone" type="tel" :value="$settings->phone" maxlength="50" autocomplete="tel" hint="Optional." />
                    <div class="form-grid__wide">
                        <x-form.textarea name="address" label="Address" :value="$settings->address" rows="3" maxlength="500" />
                    </div>
                </div>
            </x-card>

            <x-card title="Store logo">
                <div class="image-field settings-logo">
                    <figure class="image-field__preview">
                        <img
                            id="logo-preview"
                            src="{{ $settings->logo_url }}"
                            alt="{{ $settings->logo_url ? 'Current store logo' : '' }}"
                            width="160"
                            height="160"
                            @if (! $settings->logo_url) hidden @endif
                        >
                        <span class="image-field__placeholder" @if ($settings->logo_url) hidden @endif aria-hidden="true">
                            <x-icon name="image" :size="28" />
                        </span>
                        <figcaption id="logo-preview-caption" class="image-field__caption">
                            {{ $settings->logo_url ? 'Current logo' : 'No logo yet' }}
                        </figcaption>
                    </figure>

                    <div class="image-field__controls">
                        <div class="field">
                            <label class="field__label" for="logo">{{ $settings->logo_url ? 'Replace logo' : 'Upload logo' }}</label>
                            <input
                                id="logo"
                                class="field__file"
                                type="file"
                                name="logo"
                                accept="image/jpeg,image/png,image/webp"
                                aria-describedby="logo-hint @error('logo') logo-error @enderror"
                                @error('logo') aria-invalid="true" @enderror
                                data-image-input="logo-preview"
                            >
                            <p id="logo-hint" class="field__hint">JPG, PNG or WebP, up to 2 MB.</p>
                            @error('logo')
                                <p id="logo-error" class="field__error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button--primary">Save settings</button>
        </div>
    </form>
</x-layouts.admin>
