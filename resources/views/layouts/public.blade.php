<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('diamondcms.name') }}</title>
    @include('partials.brand-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {!! \App\Domains\Design\Support\DesignManager::cssVariables() !!}
    {!! diamondcms_custom_head() !!}
</head>
<body class="dc-site min-h-screen bg-background text-foreground antialiased">
    <header class="dc-header">
        <a class="dc-logo inline-flex items-center gap-2" href="{{ route('home') }}">
            <x-brand-logo variant="currentColor" class="h-7 w-7 text-primary" />
            <span>{{ config('diamondcms.name') }}</span>
        </a>
        <nav>
            <a href="{{ route('login') }}">Admin</a>
        </nav>
    </header>
    <main>
        @yield('content')
    </main>
    <footer class="dc-footer">
        <span>Powered by DiamondCMS {{ diamondcms_version() }}</span>
    </footer>
</body>
</html>
