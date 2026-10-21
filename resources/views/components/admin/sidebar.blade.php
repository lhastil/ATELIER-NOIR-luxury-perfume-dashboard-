{{-- Primary navigation: brand, sections and the signed-in administrator. --}}
@php
    $sections = [
        'Atelier' => [
            ['route' => 'dashboard', 'icon' => 'layout-grid', 'label' => 'Dashboard'],
            ['route' => 'products.index', 'icon' => 'package', 'label' => 'Products'],
            ['route' => 'categories.index', 'icon' => 'layers', 'label' => 'Categories'],
            ['route' => 'orders.index', 'icon' => 'shopping-bag', 'label' => 'Orders'],
            ['route' => 'customers.index', 'icon' => 'users', 'label' => 'Customers'],
            ['route' => 'messages.index', 'icon' => 'mail', 'label' => 'Messages'],
        ],
        'Administration' => [
            ['route' => 'profile.edit', 'icon' => 'circle-user', 'label' => 'My Profile'],
            ['route' => 'settings.edit', 'icon' => 'settings', 'label' => 'Store Settings'],
        ],
    ];

    $user = auth()->user();
@endphp

<div id="app-sidebar" class="sidebar" data-sidebar>
    <div class="sidebar__header">
        <a class="brand" href="{{ route('dashboard') }}">
            <span class="brand__name">Atelier Noir</span>
            <span class="brand__tagline">Fragrance House</span>
        </a>

        <button type="button" class="icon-button sidebar__close" aria-label="Close navigation" data-sidebar-close>
            <x-icon name="x" :size="20" />
        </button>
    </div>

    <nav class="sidebar__nav" aria-label="Main">
        @foreach ($sections as $heading => $items)
            <p class="sidebar__section-label" id="nav-{{ Str::slug($heading) }}">{{ $heading }}</p>
            <ul class="sidebar__list" role="list" aria-labelledby="nav-{{ Str::slug($heading) }}">
                @foreach ($items as $item)
                    <li><x-admin.nav-item :route="$item['route']" :icon="$item['icon']" :label="$item['label']" /></li>
                @endforeach
            </ul>
        @endforeach
    </nav>

    <div class="sidebar__footer">
        <x-avatar :name="$user?->name" />

        <div class="sidebar__identity">
            <span class="sidebar__identity-name">{{ $user?->name ?? 'Administrator' }}</span>
            <span class="sidebar__identity-meta">{{ $user?->email ?? 'Not signed in' }}</span>
        </div>

        @if ($user && Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="icon-button" aria-label="Log out">
                    <x-icon name="log-out" />
                </button>
            </form>
        @endif
    </div>
</div>
