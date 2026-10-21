{{--
    Data table in a horizontally scrollable wrapper. Columns are labels or
    ['label' => ..., 'numeric' => bool, 'hidden' => bool]. Rows go in the default
    slot; the "empty" slot is shown below the header row when there are none, and
    the header then shrinks to fit instead of scrolling.
    resources/js/ui/scroll-region.js keeps the wrapper focusable only while it overflows.
--}}
@props(['caption', 'columns' => [], 'captionId' => null])

@php
    $columns = collect($columns)->map(fn ($column) => is_array($column) ? $column : ['label' => $column]);
    $captionId = App\View\ComponentId::for($__env, 'table', $caption, $captionId);
    $isEmpty = ! $slot->hasActualContent();
@endphp

<div {{ $attributes->class(['table-region', 'table-region--empty' => $isEmpty]) }}>
    <div class="table-wrap" role="group" aria-labelledby="{{ $captionId }}" tabindex="0" data-scroll-region>
        <table class="table">
            <caption id="{{ $captionId }}" class="visually-hidden">{{ $caption }}</caption>
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th scope="col" @class(['is-numeric' => $column['numeric'] ?? false])>
                            @if ($column['hidden'] ?? false)
                                <span class="visually-hidden">{{ $column['label'] }}</span>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if ($isEmpty && isset($empty))
        {{ $empty }}
    @endif
</div>
