<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        {{-- Temporary placeholder. The admin interface is built in Phase 1. --}}
        <main>
            <h1>{{ config('app.name') }}</h1>
            <p>Project foundation is installed. The admin interface is not built yet.</p>
        </main>
    </body>
</html>
