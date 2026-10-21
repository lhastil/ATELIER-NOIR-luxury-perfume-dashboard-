{{-- Top navigation: drawer toggle, page context, search, notifications and account menu. --}}
@props(['breadcrumbs' => []])

@php($user = auth()->user())

<header class="topbar">
    <button type="button" class="icon-button topbar__menu" aria-label="Open navigation" aria-controls="app-sidebar" aria-expanded="false" data-sidebar-open>
        <x-icon name="menu" :size="20" />
    </button>

    <div class="topbar__context">
        <a class="topbar__brand" href="{{ route('dashboard') }}">Atelier Noir</a>
        <x-admin.breadcrumbs :items="$breadcrumbs" />
    </div>

    <div class="topbar__actions">
        {{-- Store-wide search is not built, so the field is shown disabled; each list page has its own search. --}}
        <form class="topbar-search" role="search">
            <label class="visually-hidden" for="topbar-search">Search</label>
            <x-icon name="search" :size="16" />
            <input id="topbar-search" class="topbar-search__input" type="search" placeholder="Search the atelier" disabled aria-describedby="topbar-search-hint">
            <span id="topbar-search-hint" class="visually-hidden">Store-wide search is not available. Use the search on the Products, Customers and Orders pages.</span>
        </form>

        <x-dropdown id="notifications-panel" label="Notifications">
            <x-slot:trigger>
                <x-icon name="bell" :size="20" />
            </x-slot:trigger>

            <div class="dropdown__header">
                <p class="dropdown__title">Notifications</p>
            </div>
            <div class="dropdown__empty">
                <x-icon name="bell" :size="20" />
                <p class="dropdown__empty-title">All quiet</p>
                <p>There are no notifications.</p>
            </div>
        </x-dropdown>

        <span class="topbar__divider" aria-hidden="true"></span>

        <x-dropdown id="account-panel" label="Account menu" trigger-class="account-trigger">
            <x-slot:trigger>
                <x-avatar :name="$user?->name" :size="34" />
                <x-icon name="chevron-down" :size="16" />
            </x-slot:trigger>

            <div class="dropdown__header">
                <p class="dropdown__identity-name">{{ $user?->name ?? 'Administrator' }}</p>
                <p class="dropdown__identity-meta">{{ $user?->email ?? 'Not signed in' }}</p>
            </div>

            @foreach ([['route' => 'profile.edit', 'icon' => 'circle-user', 'label' => 'My Profile'], ['route' => 'settings.edit', 'icon' => 'settings', 'label' => 'Store Settings']] as $link)
                @if (Route::has($link['route']))
                    <a class="dropdown__item" href="{{ route($link['route']) }}">
                        <x-icon :name="$link['icon']" />
                        {{ $link['label'] }}
                    </a>
                @else
                    <span class="dropdown__item" data-unavailable>
                        <x-icon :name="$link['icon']" />
                        <span>{{ $link['label'] }}<span class="visually-hidden"> (not available yet)</span></span>
                    </span>
                @endif
            @endforeach

            @if ($user && Route::has('logout'))
                <hr class="dropdown__divider">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown__item">
                        <x-icon name="log-out" />
                        Log out
                    </button>
                </form>
            @endif
        </x-dropdown>
    </div>
</header>
