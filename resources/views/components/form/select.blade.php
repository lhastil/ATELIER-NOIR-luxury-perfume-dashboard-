{{-- Labelled select. $options maps each value to its label. --}}
@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null])

@php
    $error = $errors->first($name);
    $selected = (string) old($name, $value);
@endphp

<div class="field">
    <label class="field__label" for="{{ $name }}">{{ $label }}</label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $attributes->class('field__input field__select') }}
        @if ($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($error)
        <p id="{{ $name }}-error" class="field__error">{{ $error }}</p>
    @endif
</div>
