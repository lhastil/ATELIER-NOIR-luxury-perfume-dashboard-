{{-- Layout for pages shown to signed-out visitors (the login page). --}}
@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f7f5f0">

        <title>{{ $title }} · {{ config('app.name') }}</title>

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        @vite(['resources/css/app.css'])
    </head>
    <body>
        <main class="auth-page">
            {{ $slot }}
        </main>
    </body>
</html>
