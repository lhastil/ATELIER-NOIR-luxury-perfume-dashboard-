{{-- Category form shared by the create and edit pages. --}}
<form method="POST" action="{{ $action }}" novalidate>
    @csrf
    @if ($category->exists)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="form-alert" role="alert">
            <p>Please correct the highlighted field below.</p>
        </div>
    @endif

    <x-card title="Category details">
        <div class="form-grid">
            <x-form.input name="name" label="Name" :value="$category->name" required maxlength="60" hint="For example: Floral, Woody or Citrus." />
        </div>
    </x-card>

    <div class="form-actions">
        <a class="button button--ghost" href="{{ route('categories.index') }}">Cancel</a>
        <button type="submit" class="button button--primary">{{ $submitLabel }}</button>
    </div>
</form>
