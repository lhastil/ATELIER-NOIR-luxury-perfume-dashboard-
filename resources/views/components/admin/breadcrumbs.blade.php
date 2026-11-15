{{--
    Breadcrumb trail. Each item is ['label' => string, 'url' => ?string]; the last item is the current page.
    Pages start their trail at the Dashboard; for roles without a dashboard (Sellers) that step is left out.
--}}
@props(['items' => []])

@php
    if (! auth()->user()?->can('access-dashboard')) {
        $items = array_values(array_filter($items, fn (array $item) => ($item['url'] ?? null) !== route('dashboard')));
    }
@endphp

<nav {{ $attributes->class('breadcrumbs') }} aria-label="Breadcrumb">
    <ol class="breadcrumbs__list" role="list">
        @foreach ($items as $item)
            <li class="breadcrumbs__item">
                @if ($loop->last)
                    <span aria-current="page">{{ $item['label'] }}</span>
                @elseif (! empty($item['url']))
                    <a class="breadcrumbs__link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    <span>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
