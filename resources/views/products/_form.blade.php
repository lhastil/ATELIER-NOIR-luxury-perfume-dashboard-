{{-- Product form shared by the create and edit pages. --}}
@use('App\Models\Product')

@php($concentrations = array_combine(Product::CONCENTRATIONS, Product::CONCENTRATIONS))

<form class="product-form" method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate>
    @csrf
    @if ($product->exists)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="form-alert" role="alert">
            <p>Please correct the highlighted {{ Str::plural('field', $errors->count()) }} below.</p>
        </div>
    @endif

    <div class="product-form__sections">
        <x-card title="Basic information">
            <div class="form-grid">
                <div class="form-grid__wide">
                    <x-form.input name="name" label="Name" :value="$product->name" required maxlength="120" />
                </div>
                <x-form.input name="sku" label="SKU" :value="$product->sku" required maxlength="32" hint="Letters, numbers and dashes, e.g. AN-FLR-004." />
                <x-form.input name="brand" label="Brand" :value="$product->brand" required maxlength="100" />
                <div class="form-grid__wide">
                    <x-form.textarea name="description" label="Description" :value="$product->description" maxlength="2000" />
                </div>
            </div>
        </x-card>

        <x-card title="Fragrance details">
            <div class="form-grid">
                <x-form.select name="category_id" label="Category" :options="$categories->pluck('name', 'id')" :value="$product->category_id" placeholder="Choose a category" required />
                <x-form.select name="concentration" label="Concentration" :options="$concentrations" :value="$product->concentration" placeholder="Choose a concentration" required />
                <x-form.input name="volume_ml" label="Volume (ml)" type="number" :value="$product->volume_ml" required min="1" max="1000" step="1" inputmode="numeric" />
            </div>
        </x-card>

        <x-card title="Pricing & inventory">
            <div class="form-grid">
                <x-form.input name="price" :label="'Price ('.config('app.currency').')'" type="number" :value="$product->price" required min="0" step="0.01" inputmode="decimal" />
                <x-form.input name="stock" label="Stock" type="number" :value="$product->stock" required min="0" step="1" inputmode="numeric" />
                <fieldset class="field field--choice">
                    <legend class="field__label">Status</legend>
                    <input type="hidden" name="is_active" value="0">
                    <label class="choice">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) @error('is_active') aria-invalid="true" aria-describedby="is_active-error" @enderror>
                        <span>Active — shown as available in the catalog</span>
                    </label>
                    @error('is_active')
                        <p id="is_active-error" class="field__error">{{ $message }}</p>
                    @enderror
                </fieldset>
            </div>
        </x-card>

        <x-card title="Product image">
            <div class="image-field">
                <figure class="image-field__preview">
                    <img
                        id="image-preview"
                        src="{{ $product->image_url }}"
                        alt=""
                        width="160"
                        height="160"
                        @if (! $product->image_url) hidden @endif
                    >
                    <span class="image-field__placeholder" @if ($product->image_url) hidden @endif aria-hidden="true">
                        <x-icon name="image" :size="28" />
                    </span>
                    <figcaption id="image-preview-caption" class="image-field__caption">
                        {{ $product->image_url ? 'Current image' : 'No image yet' }}
                    </figcaption>
                </figure>

                <div class="image-field__controls">
                    <div class="field">
                        <label class="field__label" for="image">{{ $product->image_url ? 'Replace image' : 'Upload image' }}</label>
                        <input
                            id="image"
                            class="field__file"
                            type="file"
                            name="image"
                            accept="image/jpeg,image/png,image/webp"
                            aria-describedby="image-hint @error('image') image-error @enderror"
                            @error('image') aria-invalid="true" @enderror
                            data-image-input="image-preview"
                        >
                        <p id="image-hint" class="field__hint">JPG, PNG or WebP, up to 2 MB.</p>
                        @error('image')
                            <p id="image-error" class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($product->image_path)
                        <label class="choice">
                            <input type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))>
                            <span>Remove the current image</span>
                        </label>
                    @endif
                </div>
            </div>
        </x-card>
    </div>

    <div class="form-actions">
        <a class="button button--ghost" href="{{ route('products.index') }}">Cancel</a>
        <button type="submit" class="button button--primary">{{ $submitLabel }}</button>
    </div>
</form>
