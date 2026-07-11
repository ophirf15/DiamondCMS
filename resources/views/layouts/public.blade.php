<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('diamondcms.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,650&family=Sora:wght@400;550;650&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {!! \App\Domains\Design\Support\DesignManager::cssVariables() !!}
    {!! diamondcms_custom_head() !!}
</head>
<body class="dc-site">
    <header class="dc-header">
        <a class="dc-logo" href="{{ route('home') }}">{{ config('diamondcms.name') }}</a>
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
