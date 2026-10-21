{{--
    Sidebar navigation entry. Sections whose route does not exist yet are shown
    as unavailable instead of linking to a missing page.
--}}
@props(['route', 'icon', 'label'])

@php
    // "products.index" stays active across the whole "products.*" section.
    $section = str_contains($route, '.') ? Str::beforeLast($route, '.').'.*' : $route;
@endphp

@if (Route::has($route))
    <a href="{{ route($route) }}" class="nav-item" @if (request()->routeIs($section)) aria-current="page" @endif>
        <x-icon :name="$icon" />
        <span>{{ $label }}</span>
    </a>
@else
    <span class="nav-item" data-unavailable>
        <x-icon :name="$icon" />
        <span>{{ $label }}<span class="visually-hidden"> (not available yet)</span></span>
    </span>
@endif
