{{-- Labelled multi-line text field. --}}
@props(['name', 'label', 'value' => null, 'rows' => 5])

@php($error = $errors->first($name))

<div class="field">
    <label class="field__label" for="{{ $name }}">{{ $label }}</label>
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->class('field__input field__textarea') }}
        @if ($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
    >{{ old($name, $value) }}</textarea>
    @if ($error)
        <p id="{{ $name }}-error" class="field__error">{{ $error }}</p>
    @endif
</div>
