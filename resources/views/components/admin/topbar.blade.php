{{-- Top navigation: drawer toggle, page context, search, notifications and account menu. --}}
@props(['breadcrumbs' => []])

@php($user = auth()->user())

<header class="topbar">
    <button type="button" class="icon-button topbar__menu" aria-label="Open navigation" aria-controls="app-sidebar" aria-expanded="false" data-sidebar-open>
        <x-icon name="menu" :size="20" />
    </button>

    <div class="topbar__context">
        <a class="topbar__brand" href="{{ $user?->homeUrl() ?? route('dashboard') }}">Atelier Noir</a>
        <x-admin.breadcrumbs :items="$breadcrumbs" />
    </div>

    <div class="topbar__actions">
        {{--
            Store-wide search (SearchController). Enter opens the results page;
            resources/js/ui/global-search.js also shows them below the field while typing.
        --}}
        @php($currentSearch = request()->routeIs('search') && is_string(request()->query('q')) ? request()->query('q') : '')
        <form class="topbar-search" role="search" method="GET" action="{{ route('search') }}" data-global-search>
            <label class="visually-hidden" for="topbar-search">Search</label>
            <x-icon name="search" :size="16" />
            <input id="topbar-search" class="topbar-search__input" type="search" name="q" value="{{ $currentSearch }}" placeholder="Search the atelier" autocomplete="off" aria-describedby="topbar-search-hint">
            <span id="topbar-search-hint" class="visually-hidden">Searches products, categories, customers, orders and messages. Results appear below as you type; press Enter to open the results page.</span>
            <div class="dropdown__panel dropdown__panel--end search-dropdown" hidden data-global-search-panel></div>
            <p class="visually-hidden" role="status" data-global-search-status></p>
        </form>

        {{-- $notifications and $unreadCount come from App\View\Composers\NotificationsComposer. --}}
        {{-- "Mark all as read" is sent by resources/js/ui/notifications.js without reloading the page. --}}
        <x-dropdown id="notifications-panel" :label="$unreadCount > 0 ? 'Notifications, '.$unreadCount.' unread' : 'Notifications'" trigger-class="icon-button notifications-trigger" data-notifications>
            <x-slot:trigger>
                <x-icon name="bell" :size="20" />
                @if ($unreadCount > 0)
                    <span class="notifications-badge" aria-hidden="true" data-notifications-badge>{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                @endif
            </x-slot:trigger>

            <div class="dropdown__header notifications-header">
                <p class="dropdown__title">Notifications</p>
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}" data-notifications-read-all>
                        @csrf
                        <button type="submit" class="notifications-header__action">Mark all as read</button>
                    </form>
                @endif
            </div>
            <p class="notifications-error" role="alert" hidden data-notifications-error></p>
            <p class="visually-hidden" role="status" data-notifications-status></p>

            @if ($notifications->isEmpty())
                <div class="dropdown__empty">
                    <x-icon name="bell" :size="20" />
                    <p class="dropdown__empty-title">All quiet</p>
                    <p>There are no notifications.</p>
                </div>
            @else
                <ul class="notifications-list" role="list">
                    @foreach ($notifications as $notification)
                        <li>
                            {{-- Unread items are marked as read by resources/js/ui/notifications.js before opening. --}}
                            <a @class(['dropdown__item', 'notification', 'notification--unread' => $notification['unread']]) href="{{ $notification['url'] }}" @if ($notification['unread']) data-notification-read="{{ $notification['read_url'] }}" @endif>
                                <x-icon :name="$notification['icon']" />
                                <span class="notification__text">
                                    <span class="notification__message">{{ $notification['message'] }}</span>
                                    <span class="notification__time">{{ $notification['created_at']->diffForHumans() }}</span>
                                </span>
                                @if ($notification['unread'])
                                    <span class="notification__dot" aria-hidden="true" data-notification-unread></span>
                                    <span class="visually-hidden" data-notification-unread>(unread)</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-dropdown>

        <span class="topbar__divider" aria-hidden="true"></span>

        <x-dropdown id="account-panel" label="Account menu" trigger-class="account-trigger">
            <x-slot:trigger>
                <x-avatar :name="$user?->name" :photo="$user?->profile_photo_url" :size="34" />
                <x-icon name="chevron-down" :size="16" />
            </x-slot:trigger>

            <div class="dropdown__header">
                <p class="dropdown__identity-name">{{ $user?->name ?? 'Administrator' }}</p>
                <p class="dropdown__identity-meta">{{ $user?->email ?? 'Not signed in' }}</p>
            </div>

            @foreach ([['route' => 'profile.edit', 'icon' => 'circle-user', 'label' => 'My Profile', 'section' => null], ['route' => 'settings.edit', 'icon' => 'settings', 'label' => 'Store Settings', 'section' => 'settings']] as $link)
                {{-- Store Settings only for roles that may open it. --}}
                @continue($link['section'] && ! $user?->can('access-'.$link['section']))
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
