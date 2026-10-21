{{--
    Key figure. Without a value it shows a dash, announced as "No data", rather
    than an invented number.
--}}
@props(['label', 'value' => null, 'hint' => null, 'labelId' => null])

@php($labelId = App\View\ComponentId::for($__env, 'stat', $label, $labelId))

<div {{ $attributes->class('stat-card') }} role="group" aria-labelledby="{{ $labelId }}">
    <p id="{{ $labelId }}" class="stat-card__label">{{ $label }}</p>

    @if (is_null($value) || $value === '')
        <p class="stat-card__value stat-card__value--empty"><span aria-hidden="true">—</span><span class="visually-hidden">No data</span></p>
    @else
        <p class="stat-card__value">{{ $value }}</p>
    @endif

    @if ($hint)
        <p class="stat-card__hint">{{ $hint }}</p>
    @endif
</div>
