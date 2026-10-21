{{-- Admin application layout shared by every page. --}}
@props(['title', 'breadcrumbs' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f7f5f0">

        <title>{{ $title }} · {{ config('app.name') }}</title>

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        {{--
            Enables the drawer styles before first paint. If the script bundle has not
            initialised by the load event, fall back to the static navigation.
        --}}
        <script>
            document.documentElement.classList.replace('no-js', 'js');
            window.addEventListener('load', () => {
                if (! document.documentElement.classList.contains('js-ready')) {
                    document.documentElement.classList.remove('js');
                }
            });
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <a class="skip-link" href="#main-content" data-drawer-inert>Skip to content</a>

        <div class="app-shell">
            <x-admin.sidebar />

            <div class="app-backdrop" aria-hidden="true" data-sidebar-close></div>

            <div class="app-main" data-app-main data-drawer-inert>
                <x-admin.topbar :breadcrumbs="$breadcrumbs ?? [['label' => $title]]" />

                <main id="main-content" class="app-content" tabindex="-1">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
