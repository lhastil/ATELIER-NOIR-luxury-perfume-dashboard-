{{-- Intentional empty state: decorative icon, short title and one line of explanation. --}}
@props(['title', 'icon' => null])

<div {{ $attributes->class('empty-state') }}>
    @if ($icon)
        <x-icon :name="$icon" :size="22" />
    @endif

    <p class="empty-state__title">{{ $title }}</p>

    @if ($slot->hasActualContent())
        <div class="empty-state__text">{{ $slot }}</div>
    @endif
</div>
