{{-- Content panel: titled section with an optional description and actions. --}}
@props(['title', 'description' => null, 'headingId' => null])

@php($headingId = App\View\ComponentId::for($__env, 'card', $title, $headingId))

<section {{ $attributes->class('card') }} aria-labelledby="{{ $headingId }}">
    <header class="card__header">
        <div class="card__heading">
            <h2 id="{{ $headingId }}" class="card__title">{{ $title }}</h2>

            @if ($description)
                <p class="card__description">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="card__actions">{{ $actions }}</div>
        @endisset
    </header>

    <div class="card__body">
        {{ $slot }}
    </div>
</section>
