{{-- Editorial page title with optional eyebrow, description and actions. --}}
@props(['title', 'eyebrow' => null, 'description' => null])

<header {{ $attributes->class('page-header') }}>
    <div class="page-header__text">
        @if ($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif

        <h1 class="page-header__title font-display">{{ $title }}</h1>

        @if ($description)
            <p class="page-header__description">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="page-header__actions">{{ $actions }}</div>
    @endisset
</header>
