<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Install DiamondCMS' }}</title>
    @include('partials.brand-head')
    @unless (app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/installer.ts'])
    @endunless
</head>
<body class="dark min-h-screen bg-background text-foreground antialiased">
    @yield('content')
</body>
</html>
