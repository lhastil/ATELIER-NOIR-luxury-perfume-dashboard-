{{-- Circular avatar showing initials, or a generic person icon when no name is known. --}}
@props(['name' => null, 'size' => 36])

@php
    $initials = collect(preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->class('avatar')->merge(['style' => "--avatar-size: {$size}px", 'aria-hidden' => 'true']) }}>
    @if ($initials !== '')
        {{ $initials }}
    @else
        <x-icon name="circle-user" :size="(int) round($size * 0.55)" />
    @endif
</span>
