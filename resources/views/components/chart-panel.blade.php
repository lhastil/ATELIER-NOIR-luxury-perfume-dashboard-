{{--
    Card with a fixed-height chart area, so the layout does not shift when a
    chart is drawn into it. Until chart data exists the slot holds an empty state.
--}}
@props(['title', 'description' => null, 'headingId' => null])

<x-card {{ $attributes->class('chart-panel') }} :title="$title" :description="$description" :heading-id="$headingId">
    <div class="chart-panel__area">
        {{ $slot }}
    </div>
</x-card>
