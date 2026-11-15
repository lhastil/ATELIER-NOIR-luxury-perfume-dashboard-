{{-- Circular avatar: the profile photo when there is one, otherwise initials, or a generic person icon when no name is known. --}}
@props(['name' => null, 'photo' => null, 'size' => 36])

@php
    $initials = collect(preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->class(['avatar', 'avatar--photo' => $photo])->merge(['style' => "--avatar-size: {$size}px", 'aria-hidden' => 'true']) }}>
    @if ($photo)
        <img class="avatar__image" src="{{ $photo }}" alt="" width="{{ $size }}" height="{{ $size }}">
    @elseif ($initials !== '')
        {{ $initials }}
    @else
        <x-icon name="circle-user" :size="(int) round($size * 0.55)" />
    @endif
</span>
