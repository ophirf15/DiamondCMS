<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'DiamondCMS') }}</title>
    @isset($description)<meta name="description" content="{{ $description }}">@endisset
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? config('app.name', 'DiamondCMS') }}">
    @isset($description)<meta property="og:description" content="{{ $description }}">@endisset
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,650&family=Sora:wght@400;550;650&display=swap" rel="stylesheet">
    @unless (app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endunless
    {!! \App\Domains\Design\Support\DesignManager::cssVariables() !!}
    {!! diamondcms_custom_head() !!}
</head>
<body class="dc-site">
    <a class="skip-link" href="#content">Skip to content</a>
    <header class="dc-header">
        <a class="dc-logo" href="{{ route('home') }}">{{ config('app.name', 'DiamondCMS') }}</a>
        <nav aria-label="Primary">
            <a href="{{ route('projects.index') }}">Projects</a>
            <a href="{{ route('login') }}">Admin</a>
        </nav>
    </header>
    <main id="content">
        @if (session('status'))
            <p class="dc-status" role="status">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
    <footer class="dc-footer">
        <span>Powered by DiamondCMS {{ diamondcms_version() }}</span>
    </footer>
</body>
</html>
