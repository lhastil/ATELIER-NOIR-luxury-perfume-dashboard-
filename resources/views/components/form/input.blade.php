{{-- Labelled text input with its validation message; keeps the submitted value after an error. --}}
@props(['name', 'label', 'value' => null, 'type' => 'text', 'hint' => null])

@php
    $error = $errors->first($name);
    $describedBy = collect([$hint ? "{$name}-hint" : null, $error ? "{$name}-error" : null])->filter()->implode(' ');
@endphp

<div class="field">
    <label class="field__label" for="{{ $name }}">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        {{ $attributes->class('field__input') }}
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
    >
    @if ($hint)
        <p id="{{ $name }}-hint" class="field__hint">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $name }}-error" class="field__error">{{ $error }}</p>
    @endif
</div>
