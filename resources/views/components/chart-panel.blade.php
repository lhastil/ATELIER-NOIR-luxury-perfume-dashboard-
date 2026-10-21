{{--
    Card with a fixed-height chart area, so the layout does not shift when a
    chart is drawn into it. The slot holds the chart, or an empty state when there is no data.
--}}
@props(['title', 'description' => null, 'headingId' => null])

<x-card {{ $attributes->class('chart-panel') }} :title="$title" :description="$description" :heading-id="$headingId">
    <div class="chart-panel__area">
        {{ $slot }}
    </div>
</x-card>
