{{--
    Global search results, grouped by section. Shown on the search page and,
    loaded by resources/js/ui/global-search.js, in the top bar dropdown. It has
    no element ids, because both can be on the same page.
    $results is null when the search is too short to run.
--}}
@php
    $total = $results === null ? 0 : collect($results)->sum(fn ($items) => $items->count());
@endphp

<div class="search-results" data-search-count="{{ $total }}">
    @if ($results === null)
        <p class="search-results__note">Type at least {{ App\Http\Controllers\SearchController::MIN_LENGTH }} characters to search products, categories, customers, orders and messages.</p>
    @elseif ($total === 0)
        <x-empty-state icon="search" title="No results">
            Nothing matches "{{ $search }}". Try another name, email, SKU or order number.
        </x-empty-state>
    @else
        @foreach ($results as $group => $items)
            @continue($items->isEmpty())

            <section class="search-results__group">
                <h2 class="search-results__heading">{{ $group }}</h2>
                <ul class="search-results__list" role="list">
                    @foreach ($items as $item)
                        @php
                            [$url, $label, $detail] = match ($group) {
                                'Products' => [route('products.edit', $item), $item->name, $item->sku],
                                'Categories' => [route('categories.edit', $item), $item->name, $item->products_count.' '.Str::plural('product', $item->products_count)],
                                'Customers' => [route('customers.show', $item), $item->name, $item->email],
                                'Orders' => [route('orders.show', $item), $item->number, $item->customer->name.' · '.$item->placed_at->isoFormat('D MMM YYYY')],
                                'Messages' => [route('messages.show', $item), $item->name, $item->subject],
                            };
                        @endphp
                        <li>
                            <a class="search-result" href="{{ $url }}">
                                <span class="search-result__label">{{ $label }}</span>
                                <span class="search-result__detail">{{ $detail }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    @endif
</div>
